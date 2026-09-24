<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockIn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    /**
     * Show the overview page.
     */
    public function index()
    {
        return view('overview');
    }

    /**
     * Show the products page.
     */
    public function products()
    {
        return view('products');
    }

    /**
     * SS-87: Show the standalone Stock-In / Receiving page.
     */
    public function stockIn()
    {
        return view('stock-in');
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
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'quantity_received' => ['required', 'integer', 'min:1'],
            'unit_of_measure' => ['required', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $validated['supplier_id'] = $validated['supplier_id'] ?? null;
        $validated['note'] = $validated['note'] ?? null;
 
        $transactionProduct = null;
        $unitMismatch = false;
        $piecesPerReceivingUnit = 0;
        $pieceDelta = 0;
        $stockBefore = 0;
        $stockAfter = 0;

        DB::transaction(function () use ($user, $validated, &$transactionProduct, &$unitMismatch, &$piecesPerReceivingUnit, &$pieceDelta, &$stockBefore, &$stockAfter) {
            $query = Product::where('id', $validated['product_id'])
                ->where('user_id', $user->id);

            // SQLite does not support lockForUpdate; the transaction plus
            // incremental update still serializes stock mutations safely.
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $query = $query->lockForUpdate();
            }

            $product = $query->first();
            if (! $product) {
                return;
            }

            $transactionProduct = $product;

            if ($validated['unit_of_measure'] !== $product->receiving_unit) {
                $unitMismatch = true;
                return;
            }

            $piecesPerReceivingUnit = (int) $product->pieces_per_receiving_unit;
            $pieceDelta = (int) $validated['quantity_received'] * $piecesPerReceivingUnit;
            $stockBefore = (int) $product->current_stock;
            $stockAfter = $stockBefore + $pieceDelta;

            $updatedRows = Product::where('id', $product->id)
                ->increment('current_stock', $pieceDelta);

            if ($updatedRows !== 1) {
                throw new \RuntimeException('Unable to update product stock.');
            }

            StockIn::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'supplier_id' => $validated['supplier_id'],
                'quantity_received' => $validated['quantity_received'],
                'unit_of_measure' => $validated['unit_of_measure'],
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

        if ($unitMismatch) {
            return response()->json([
                'message' => 'Unit of measure must match the product\'s receiving unit (' . $transactionProduct->receiving_unit . ')',
                'unit_of_measure' => ['The unit of measure must match the product\'s configured receiving unit.']
            ], 422);
        }

        $updatedProduct = Product::where('id', $transactionProduct->id)->first();

        return response()->json([
            'message' => 'Stock received successfully',
            'stock_in' => [
                'product_id' => $transactionProduct->id,
                'product_name' => $transactionProduct->name,
                'quantity_received' => $validated['quantity_received'],
                'unit_of_measure' => $validated['unit_of_measure'],
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
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->where(fn ($q) => $q->where('user_id', $user->id)),
            ],
            'category' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'current_stock' => 'required|integer|min:0',
            'reorder_threshold' => 'required|integer|min:0',
        ]);

        $validated['user_id'] = $user->id;
        $product = Product::create($validated);

        return response()->json(['message' => 'Product added successfully', 'product' => $product], 201);
    }

    /**
     * Update an existing product.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user || $product->user_id !== $user->id) {
            return response()->json(['message' => 'Product not found or access denied.'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'sku' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('products', 'sku')
                    ->where(fn ($q) => $q->where('user_id', $user->id))
                    ->ignore($product->id),
            ],
            'category' => 'sometimes|nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'current_stock' => 'sometimes|integer|min:0',
            'reorder_threshold' => 'sometimes|integer|min:0',
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
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'sku')
                    ->where(fn ($q) => $q->where('user_id', $user->id))
                    ->ignore($request->input('id')),
            ],
            'category' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'current_stock' => 'required|integer|min:0',
            'reorder_threshold' => 'required|integer|min:0',
        ]);

        $product = Product::where('id', $validated['id'])
            ->where('user_id', $user->id)
            ->first();

        if (! $product) {
            return response()->json(['message' => 'Product not found or access denied.'], 404);
        }

        // SS-85: Execute SQL UPDATE query for the selected product ID.
        $updateData = collect($validated)
            ->except(['id'])
            ->filter(fn ($value) => $value !== null)
            ->toArray();

        $updatedRows = Product::where('id', $product->id)->update($updateData);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product->fresh(),
            'updated_rows' => $updatedRows,
        ]);
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

        $lowStockProducts = Product::ownedBy($user->id)
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
 * Get all products sorted by latest.
 */
public function getProducts(): JsonResponse
{
    $user = $this->currentUser();
    if (! $user) {
        return response()->json([], 401);
    }

    $products = Product::ownedBy($user->id)
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
        ->latest()->get();

    return response()->json($products);
}

    /**
     * Remove a product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user || $product->user_id !== $user->id) {
            return response()->json(['message' => 'Product not found or access denied.'], 404);
        }

        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
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

        $product = Product::where('id', $validated['product_id'])
            ->where('user_id', $user->id)
            ->first();

        if (! $product) {
            return response()->json(['message' => 'Product not found or access denied.'], 404);
        }

        $delta = (int) $validated['delta'];
        $stockBefore = (int) $product->current_stock;
        $stockAfter = $stockBefore + $delta;

        if ($stockAfter < 0) {
            throw ValidationException::withMessages([
                'delta' => 'Insufficient stock. Current stock is ' . $stockBefore . '.',
            ]);
        }

        DB::transaction(function () use ($product, $user, $validated, $delta, $stockBefore, $stockAfter) {
            Product::where('id', $product->id)->update([
                'current_stock' => $stockAfter,
            ]);

            StockAdjustment::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'reason' => $validated['reason'],
                'reason_note' => $validated['reason_note'] ?? null,
                'delta' => $delta,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
            ]);
        });

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
