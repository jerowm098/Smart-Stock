<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockAdjustment;
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

        $products = Product::ownedBy($user->id)->latest()->get();
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
