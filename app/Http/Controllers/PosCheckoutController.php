<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PosCheckoutController extends Controller
{
    /**
     * Resolve the authenticated user used for ownership checks.
     */
    protected function currentUser(): ?\App\Models\User
    {
        return Auth::user();
    }

    /**
     * Show the POS checkout page.
     */
    public function index()
    {
        return view('cashier.cashier-pos');
    }

    /**
     * Expand a stored unit-of-measure value into a complete English word.
     *
     * Inventory may store short forms ("pc", "kg"), but the POS must never show
     * abbreviations to the cashier. Unknown values pass through unchanged so a
     * shop-specific unit ("tray", "bundle") is never silently rewritten.
     */
    protected function unitWord(string $unit): string
    {
        $key = strtolower(trim($unit));

        $aliases = [
            'pc' => 'piece', 'pcs' => 'piece', 'pce' => 'piece',
            'piece' => 'piece', 'pieces' => 'piece',
            'kg' => 'kilogram', 'kgs' => 'kilogram', 'kilo' => 'kilogram',
            'kilos' => 'kilogram', 'kilogram' => 'kilogram', 'kilograms' => 'kilogram',
            'g' => 'gram', 'gr' => 'gram', 'gram' => 'gram', 'grams' => 'gram',
            'mg' => 'milligram', 'milligram' => 'milligram', 'milligrams' => 'milligram',
            'l' => 'liter', 'lt' => 'liter', 'ltr' => 'liter', 'liter' => 'liter',
            'liters' => 'liter', 'litre' => 'liter', 'litres' => 'liter',
            'ml' => 'milliliter', 'milliliter' => 'milliliter', 'milliliters' => 'milliliter',
            'm' => 'meter', 'mtr' => 'meter', 'meter' => 'meter', 'meters' => 'meter',
            'metre' => 'meter', 'metres' => 'meter',
            'cm' => 'centimeter', 'centimeter' => 'centimeter', 'centimeters' => 'centimeter',
            'mm' => 'millimeter', 'millimeter' => 'millimeter', 'millimeters' => 'millimeter',
            'ft' => 'foot', 'foot' => 'foot', 'feet' => 'foot',
            'in' => 'inch', 'inch' => 'inch', 'inches' => 'inch',
            'bx' => 'box', 'box' => 'box', 'boxes' => 'box',
            'sck' => 'sack', 'sack' => 'sack', 'sacks' => 'sack',
            'bag' => 'bag', 'bags' => 'bag', 'roll' => 'roll', 'rolls' => 'roll',
            'set' => 'set', 'sets' => 'set', 'pack' => 'pack', 'packs' => 'pack',
            'bundle' => 'bundle', 'bundles' => 'bundle', 'can' => 'can', 'cans' => 'can',
            'bottle' => 'bottle', 'bottles' => 'bottle', 'tube' => 'tube', 'tubes' => 'tube',
            'sheet' => 'sheet', 'sheets' => 'sheet', 'board' => 'board', 'boards' => 'board',
            'gallon' => 'gallon', 'gallons' => 'gallon', 'length' => 'length', 'lengths' => 'length',
        ];

        return $aliases[$key] ?? ($key !== '' ? $key : 'piece');
    }

    /**
     * Cashier-safe product catalog for the POS screen.
     *
     * BRD (POS): "The system shall allow the user to search for an item by its
     * name or ID" and staff may browse available hardware items.
     *
     * BRD (Inventory) Security keeps the full Admin inventory endpoints closed to
     * Staff, so this endpoint exposes only the fields a cashier legitimately
     * needs — no supplier data, no audit timestamps. Deactivated products are
     * excluded so discontinued items cannot be rung up.
     */
    public function catalog(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $search = trim((string) $request->query('search', ''));

        $query = Product::query()
            ->where('is_active', true)
            // reorder_threshold is included because the POS stock badge needs it to
            // show the same low/out-of-stock styling as the Admin inventory list.
            // The receiving-unit metadata is included so the POS can state, per
            // row, which unit of measure is actually being sold.
            ->select([
                'id', 'name', 'sku', 'category', 'price', 'current_stock',
                'reorder_threshold', 'receiving_unit', 'pieces_per_receiving_unit',
                'updated_at',
            ])
            // Most recently added-or-updated products first, consistent with the
            // Admin tables: a newly stocked or newly added item floats to the
            // top so cashiers immediately see what changed in the catalog.
            ->orderByDesc('updated_at');

        if ($search !== '') {
            // LIKE instead of ILIKE: PostgreSQL's LIKE is already
            // case-insensitive for ASCII, and ILIKE is a hard syntax error on
            // MySQL/SQLite. One portable statement, no driver branching.
            $like = '%'.$search.'%';

            $query->where(function ($q) use ($search, $like) {
                $q->where('name', 'LIKE', $like)
                    ->orWhere('sku', 'LIKE', $like);

                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        return response()->json($query->get()->map(fn (Product $product) => $this->formatCatalogProduct($product)));
    }

    /**
     * Shape a product row for the POS: cashier-safe fields plus the unit of
     * measure actually being sold. Shared by the catalog endpoint and the
     * checkout response (so the UI can update stock without a reload).
     */
    private function formatCatalogProduct(Product $product): array
    {
        $piecesPerUnit = (int) ($product->pieces_per_receiving_unit ?: 1);
        $receivingUnit = trim((string) ($product->receiving_unit ?: '')) ?: 'piece';

        // What the cashier hands over is the base stock unit, because
        // current_stock and price are both kept per base unit. When the
        // receiving unit packs several pieces (1 box = 10), the base unit is
        // the piece; when it is already the base unit (1 kilo = 1), it is
        // sold as-is. This is the "unit of measure" shown on the POS.
        $sellUnit = $this->unitWord($piecesPerUnit > 1 ? 'piece' : $receivingUnit);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'category' => $product->category,
            'price' => $product->price,
            'current_stock' => $product->current_stock,
            'reorder_threshold' => $product->reorder_threshold,
            'updated_at' => $product->updated_at,
            'receiving_unit' => $this->unitWord($receivingUnit),
            'pieces_per_receiving_unit' => $piecesPerUnit,
            // Always a complete word ("piece", "kilogram") — never "pc"/"kg".
            'sell_unit' => $sellUnit,
        ];
    }

    /**
     * Process POS checkout - atomic transaction for sale and inventory deduction.
     */
    public function checkout(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        try {
            $validated = $request->validate([
                'cart_items' => 'required|array|min:1',
                'cart_items.*.product_id' => 'required|integer|exists:products,id',
                'cart_items.*.quantity' => 'required|integer|min:1',
                'payment_amount' => 'required|numeric|min:0',
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $exception->errors(),
            ], 422);
        }

        return DB::transaction(function () use ($user, $validated) {
            $cartItems = $this->normalizeCartItems($validated['cart_items']);
            $paymentAmount = (float) $validated['payment_amount'];

            $subtotal = 0.0;
            $saleItemsData = [];

            foreach ($cartItems as $cartItem) {
                $product = Product::findOrFail($cartItem['product_id']);

                // BRD (Inventory): discontinued items are deactivated, so they must
                // not be sellable even if a stale cart still references them.
                if (! $product->is_active) {
                    return response()->json([
                        'message' => 'This item is no longer available.',
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                    ], 409);
                }

                if ($product->current_stock < $cartItem['quantity']) {
                    return response()->json([
                        'message' => 'Insufficient stock.',
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'available_stock' => $product->current_stock,
                        'requested_quantity' => $cartItem['quantity'],
                    ], 409);
                }

                $unitPrice = (float) $product->price;
                $lineTotal = round($unitPrice * $cartItem['quantity'], 2);
                $subtotal = round($subtotal + $lineTotal, 2);

                $saleItemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $cartItem['quantity'],
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            // No tax applied: the store sells at listed price (VAT-inclusive).
            $totalAmount = $subtotal;

            if ($paymentAmount < $totalAmount) {
                return response()->json([
                    'message' => 'Payment amount is insufficient.',
                    'total_amount' => $totalAmount,
                    'payment_amount' => $paymentAmount,
                ], 402);
            }

            $changeAmount = round($paymentAmount - $totalAmount, 2);

            $sale = Sale::create([
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'payment_amount' => $paymentAmount,
                'change_amount' => $changeAmount,
            ]);

            foreach ($saleItemsData as $itemData) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'line_total' => $itemData['line_total'],
                ]);

                $stockUpdated = Product::whereKey($itemData['product_id'])
                    ->where('current_stock', '>=', $itemData['quantity'])
                    ->decrement('current_stock', $itemData['quantity']);

                if ($stockUpdated < 1) {
                    throw new \RuntimeException('Stock changed while processing checkout.');
                }
            }

            return response()->json([
                'message' => 'Checkout completed successfully.',
                'sale_id' => $sale->id,
                'subtotal_amount' => $subtotal,
                'total_amount' => $totalAmount,
                'payment_amount' => $paymentAmount,
                'change_amount' => $changeAmount,
                'change' => $changeAmount,
                // Post-sale stock snapshot in the same shape as the catalog, so
                // the POS can refresh its product table the instant the sale
                // completes instead of waiting for a page reload.
                'products' => Product::query()
                    ->whereIn('id', array_column($saleItemsData, 'product_id'))
                    ->get()
                    ->map(fn (Product $product) => $this->formatCatalogProduct($product))
                    ->all(),
            ], 200);
        });
    }

    /**
     * Combine duplicate product selections so each product is checked and deducted once.
     */
    private function normalizeCartItems(array $cartItems): array
    {
        $normalized = [];

        foreach ($cartItems as $cartItem) {
            $productId = (int) $cartItem['product_id'];
            $quantity = (int) $cartItem['quantity'];

            if (! isset($normalized[$productId])) {
                $normalized[$productId] = [
                    'product_id' => $productId,
                    'quantity' => 0,
                ];
            }

            $normalized[$productId]['quantity'] += $quantity;
        }

        return array_values($normalized);
    }
}