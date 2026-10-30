<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockIn;
use App\Services\ActivityLogger;
use App\Services\SkuGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    /**
     * Generate a unique SKU for a product created on the fly from the
     * Receive Stock modal: a short English-derived prefix plus a sequential
     * zero-padded number (e.g. "Trigon Paint 1L" -> "TRI-001"). The full name
     * stays editable on the Products page.
     *
     * The prefix comes from the ENGLISH form of the name, so a Filipino entry
     * still yields a readable code: "Pako" -> "NAI-001", not "PAK-...".
     */
    protected function generateSku(string $name): string
    {
        return SkuGenerator::next($name);
    }

    /**
     * SS-88: Store a newly received stock-in transaction.
     * Validates input, checks ownership, performs atomic stock update,
     * and logs the receiving transaction with audit trail.
     */
    public function storeStockIn(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $validated = $request->validate([
            'product_id' => ['nullable', 'integer', 'min:1'],
            'new_product_name' => ['nullable', 'string', 'max:255'],
            // Optional override for the auto-generated SKU of a NEW product.
            'custom_sku' => ['nullable', 'string', 'max:50'],
            // Optional category for a NEW product; defaults to Uncategorized.
            'custom_category' => ['nullable', 'string', 'max:100'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'quantity_received' => ['required', 'integer', 'min:1'],
            // The Receiving Unit dropdown pre-selects the product's configured
            // unit, so this is normally automatic. An override is a deliberate
            // choice by the admin and is recorded on the transaction as-is;
            // when absent the server derives it from the product.
            'unit_of_measure' => ['nullable', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $validated['supplier_id'] = $validated['supplier_id'] ?? null;
        $validated['note'] = $validated['note'] ?? null;

        $newProductName = trim((string) ($validated['new_product_name'] ?? ''));
        $hasProductId = ! empty($validated['product_id']);
        if (! $hasProductId && $newProductName === '') {
            return response()->json([
                'message' => 'Select an existing product or specify a new product name.',
            ], 422);
        }

        // A hand-typed SKU must be free before we start the transaction;
        // otherwise the unique constraint would abort the whole receive.
        $customSku = strtoupper(trim((string) ($validated['custom_sku'] ?? '')));
        if (! $hasProductId && $customSku !== '' && Product::where('sku', $customSku)->exists()) {
            return response()->json([
                'message' => "SKU \"{$customSku}\" is already in use. Please choose another.",
            ], 422);
        }

        $transactionProduct = null;
        $newProductCreated = false;
        $unitOfMeasure = 'piece';
        $piecesPerReceivingUnit = 0;
        $pieceDelta = 0;
        $stockBefore = 0;
        $stockAfter = 0;
        $stockIn = null;

        DB::transaction(function () use ($user, $validated, $newProductName, $hasProductId, $customSku, &$transactionProduct, &$newProductCreated, &$unitOfMeasure, &$piecesPerReceivingUnit, &$pieceDelta, &$stockBefore, &$stockAfter, &$stockIn) {
            if ($hasProductId) {
                $query = Product::where('id', $validated['product_id']);

                // SQLite does not support lockForUpdate; the transaction plus
                // incremental update still serializes stock mutations safely.
                if (DB::connection()->getDriverName() !== 'sqlite') {
                    $query = $query->lockForUpdate();
                }

                $product = $query->first();
                if (! $product) {
                    return;
                }
            } else {
                // "Other…" path: the admin is receiving stock for a brand-new
                // item, so create its catalog record first. It starts at price
                // 0 with a single-piece conversion; both are editable on the
                // Products page afterwards. The chosen Receiving Unit becomes
                // the new product's configured receiving unit.
                $product = Product::create([
                    'name' => $newProductName,
                    'sku' => $customSku !== '' ? $customSku : $this->generateSku($newProductName),
                    'category' => trim((string) ($validated['custom_category'] ?? '')) ?: 'Uncategorized',
                    'price' => 0,
                    'current_stock' => 0,
                    'reorder_threshold' => 0,
                    'user_id' => $user->id,
                    'is_active' => true,
                    'receiving_unit' => trim((string) ($validated['unit_of_measure'] ?? '')) ?: 'piece',
                    'pieces_per_receiving_unit' => 1,
                ]);
                $newProductCreated = true;
            }

            $transactionProduct = $product;

            $productUnit = trim((string) ($product->receiving_unit ?: '')) ?: 'piece';

            // Record the unit the admin actually selected for this delivery
            // ("per kilo", "box", …); fall back to the product's configured
            // receiving unit when the client did not send one.
            $requestedUnit = trim((string) ($validated['unit_of_measure'] ?? ''));
            $unitOfMeasure = $requestedUnit !== '' ? $requestedUnit : $productUnit;

            $piecesPerReceivingUnit = (int) $product->pieces_per_receiving_unit;
            $pieceDelta = (int) $validated['quantity_received'] * $piecesPerReceivingUnit;
            $stockBefore = (int) $product->current_stock;
            $stockAfter = $stockBefore + $pieceDelta;

            $updatedRows = Product::where('id', $product->id)
                ->increment('current_stock', $pieceDelta);

            if ($updatedRows !== 1) {
                throw new \RuntimeException('Unable to update product stock.');
            }

            $stockIn = StockIn::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'supplier_id' => $validated['supplier_id'],
                'quantity_received' => $validated['quantity_received'],
                'unit_of_measure' => $unitOfMeasure,
                'unit_conversion' => $piecesPerReceivingUnit,
                'piece_delta' => $pieceDelta,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'note' => $validated['note'],
            ]);
        });

        if (! $transactionProduct) {
            return response()->json(['message' => 'Product not found or access denied.'], 404);
        }

        // Audit feed: log the receive (and the brand-new product, if any).
        ActivityLogger::safe(function () use ($user, $stockIn, $transactionProduct, $newProductCreated) {
            if ($newProductCreated) {
                ActivityLogger::productCreated($user, $transactionProduct);
            }
            if ($stockIn) {
                ActivityLogger::stockReceived($user, $stockIn, $transactionProduct);
            }
        });

        $updatedProduct = Product::where('id', $transactionProduct->id)->first();

        return response()->json([
            'message' => $newProductCreated
                ? 'New product created and stock received successfully'
                : 'Stock received successfully',
            'stock_in' => [
                'product_id' => $transactionProduct->id,
                'product_name' => $transactionProduct->name,
                'quantity_received' => $validated['quantity_received'],
                'unit_of_measure' => $unitOfMeasure,
                'unit_conversion' => $piecesPerReceivingUnit,
                'piece_delta' => $pieceDelta,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'supplier_id' => $validated['supplier_id'],
                'note' => $validated['note'],
            ],
            'product' => $updatedProduct,
        ], 201);
    }

    /**
     * Resolve the authenticated user used for ownership checks.
     */
    protected function currentUser(): ?\App\Models\User
    {
        return Auth::user();
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')],
            'category' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'current_stock' => 'required|integer|min:0',
            'reorder_threshold' => 'required|integer|min:0',
            'receiving_unit' => 'nullable|string|max:20',
        ]);

        // A blank SKU gets a document-style code (e.g. "EXA-001") so items never
        // land in the catalogue without a proper item number.
        $validated['sku'] = trim((string) ($validated['sku'] ?? '')) !== ''
            ? strtoupper(trim($validated['sku']))
            : $this->generateSku($validated['name']);

        // user_id is kept purely as a "created by" audit trail; the catalogue
        // itself is shared store-wide across every account.
        $validated['user_id'] = $user->id;
        $product = Product::create($validated);

        ActivityLogger::safe(fn () => ActivityLogger::productCreated($user, $product));

        return response()->json(['message' => 'Product added successfully', 'product' => $product], 201);
    }

    /**
     * Update an existing product.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        if (! $this->currentUser()) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'sku' => ['sometimes', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product->id)],
            'category' => 'sometimes|nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'current_stock' => 'sometimes|integer|min:0',
            'reorder_threshold' => 'sometimes|integer|min:0',
            'receiving_unit' => 'sometimes|nullable|string|max:20',
        ]);

        $product->update($validated);

        return response()->json(['message' => 'Product updated successfully', 'product' => $product->fresh()]);
    }

    /**
     * SS-84/SS-85: Update a product by ID using a SQL UPDATE query.
     *
     * Accepts the product ID and the fields to update, validates the input,
     * and executes a database update for the selected product.
     */
    public function updateProduct(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'name' => 'required|string|max:255',
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($request->input('id'))],
            'category' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'current_stock' => 'required|integer|min:0',
            'reorder_threshold' => 'required|integer|min:0',
            'receiving_unit' => 'nullable|string|max:20',
        ]);

        $product = Product::find($validated['id']);

        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        // SS-85: Execute SQL UPDATE query for the selected product ID.
        $updateData = collect($validated)
            ->except(['id'])
            ->filter(fn ($value) => $value !== null)
            ->toArray();

        // Snapshot the editable fields BEFORE the update so the activity feed
        // can describe exactly what changed (e.g. a rename or price change).
        $before = $product->only([
            'name', 'sku', 'category', 'price', 'receiving_unit', 'reorder_threshold',
        ]);

        $updatedRows = Product::where('id', $product->id)->update($updateData);

        $fresh = $product->fresh();
        ActivityLogger::safe(fn () => ActivityLogger::productUpdated($user, $fresh, $before));

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $fresh,
            'updated_rows' => $updatedRows,
        ]);
    }

    /**
     * Defect Items: number of damaged stock adjustments recorded.
     *
     * A "defect item" is any stock_adjustments row logged with reason
     * 'damaged' (see adjustStock). Surfaced as a summary stat on the
     * Products page alongside the stock counts.
     */
    public function defectCount(): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $count = StockAdjustment::where('reason', 'damaged')->count();

        return response()->json(['defect_count' => $count]);
    }

    /**
     * Get products with low stock.
     */
    public function getAlerts(): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user) {
            return response()->json([], 401);
        }

        $lowStockProducts = Product::where('is_active', true)
            ->whereColumn('current_stock', '<=', 'reorder_threshold')
            ->addSelect([
                'last_received_at' => function ($query) {
                    $query->select('stock_ins.created_at')
                        ->from('stock_ins')
                        ->whereColumn('stock_ins.product_id', 'products.id')
                        ->orderByDesc('stock_ins.created_at')
                        ->limit(1);
                },
                'last_adjusted_at' => function ($query) {
                    $query->select('stock_adjustments.created_at')
                        ->from('stock_adjustments')
                        ->whereColumn('stock_adjustments.product_id', 'products.id')
                        ->orderByDesc('stock_adjustments.created_at')
                        ->limit(1);
                }
            ])
            ->get();

        return response()->json($lowStockProducts);
    }
/**
 * Get all products for the Admin inventory master list.
 *
 * BRD (Inventory Management): the Admin must be able to see — and bring back —
 * discontinued items, so deactivated products are included here and flagged via
 * the `is_active` flag. Staff never reach this endpoint; the POS uses
 * /api/pos/products, which filters them out.
 */
public function getProducts(Request $request): JsonResponse
{
    $user = $this->currentUser();
    if (! $user) {
        return response()->json([], 401);
    }

    // ?include_inactive=0 lets a caller narrow the list back to active items.
    $products = Product::query()
        ->when(! $request->boolean('include_inactive', true),
            fn ($q) => $q->where('is_active', true))
        ->addSelect([
            'last_supplier_name' => function ($query) {
                $query->select('suppliers.name')
                    ->from('stock_ins')
                    ->join('suppliers', 'stock_ins.supplier_id', '=', 'suppliers.id')
                    ->whereColumn('stock_ins.product_id', 'products.id')
                    ->orderByDesc('stock_ins.created_at')
                    ->limit(1);
            },
            'last_received_at' => function ($query) {
                $query->select('stock_ins.created_at')
                    ->from('stock_ins')
                    ->whereColumn('stock_ins.product_id', 'products.id')
                    ->orderByDesc('stock_ins.created_at')
                    ->limit(1);
            }
        ])
        // Most recently added-or-updated products float to the top of the
        // Products and Stock-In tables. updated_at is bumped by every flow:
        // add, edit, price change, stock adjustment and stock-in receive.
        ->latest('updated_at')->get();

    // The last_received_at subselect returns a naive "YYYY-MM-DD HH:MM:SS"
    // string straight from Postgres. Without a timezone marker the browser's
    // Date() parses it as LOCAL time, shifting every "Latest Received" label
    // by the UTC offset (e.g. +8 hrs in Manila). Normalize to ISO-8601 UTC.
    $products->each(function ($product) {
        if (! empty($product->last_received_at)) {
            $product->last_received_at = \Carbon\Carbon::parse($product->last_received_at)
                ->timezone('UTC')
                ->toISOString();
        }
    });

    return response()->json($products);
}

    /**
     * Deactivate a product instead of deleting it.
     *
     * BRD (Inventory Management): "Editing existing product details or
     * deactivating discontinued items."
     *
     * Hard deletion would orphan historical sale_items / stock_ins /
     * stock_adjustments rows, so `is_active` is toggled instead. The item
     * disappears from the inventory master list and the POS catalog but its
     * audit trail survives.
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        if (! $user = $this->currentUser()) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        // ?reactivate=1 brings a discontinued item back into the catalogue.
        if ($request->boolean('reactivate')) {
            $product->update(['is_active' => true]);

            ActivityLogger::safe(fn () => ActivityLogger::productReactivated($user, $product));

            return response()->json([
                'message' => 'Product reactivated successfully',
                'product' => $product->fresh(),
            ]);
        }

        $product->update(['is_active' => false]);

        ActivityLogger::safe(fn () => ActivityLogger::productDeactivated($user, $product));

        return response()->json([
            'message' => 'Product deactivated successfully',
            'product' => $product->fresh(),
        ]);
    }

    /**
     * SS-38 / SS-97: Manual stock adjustment.
     *
     * Accepts a product ID, a reason (damaged/lost/internal_transfer/correction),
     * an optional note, and a non-zero delta. Updates the product's current_stock
     * and records an audit-trail entry (SS-24) in the `stock_adjustments` table
     * with the timestamp and the responsible admin.
     */
    public function adjustStock(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'in:damaged,lost,internal_transfer,correction'],
            'reason_note' => ['nullable', 'string', 'max:255'],
            'delta' => ['required', 'integer', 'not_in:0'],
        ]);

        $product = Product::find($validated['product_id']);

        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $delta = (int) $validated['delta'];
        $stockBefore = (int) $product->current_stock;
        $stockAfter = $stockBefore + $delta;
        $adjustment = null;

        if ($stockAfter < 0) {
            throw ValidationException::withMessages([
                'delta' => 'Insufficient stock. Current stock is ' . $stockBefore . '.',
            ]);
        }

        DB::transaction(function () use ($product, $user, $validated, $delta, $stockBefore, $stockAfter, &$adjustment) {
            Product::where('id', $product->id)->update([
                'current_stock' => $stockAfter,
            ]);

            $adjustment = StockAdjustment::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'reason' => $validated['reason'],
                'reason_note' => $validated['reason_note'] ?? null,
                'delta' => $delta,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
            ]);
        });

        ActivityLogger::safe(fn () => ActivityLogger::stockAdjusted($user, $adjustment, $product));

        return response()->json([
            'message' => 'Stock adjusted successfully',
            'product' => $product->fresh(),
            'adjustment' => [
                'reason' => $validated['reason'],
                'delta' => $delta,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
            ],
        ]);
    }
}
