<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Show the dashboard.
     */
    public function index()
    {
        return view('dashboard');
    }

    /**
     * Show the products page.
     */
    public function products()
    {
        return view('products');
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku',
            'category' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'current_stock' => 'required|integer|min:0',
            'reorder_threshold' => 'required|integer|min:0',
        ]);

        $product = Product::create($validated);

        return response()->json(['message' => 'Product added successfully', 'product' => $product], 201);
    }

    /**
     * Get products with low stock.
     */
    public function getAlerts(): JsonResponse
    {
        $lowStockProducts = Product::whereColumn('current_stock', '<=', 'reorder_threshold')->get();
        return response()->json($lowStockProducts);
    }

    /**
     * Get all products sorted by latest.
     */
    public function getProducts(): JsonResponse
    {
        $products = Product::latest()->get();
        return response()->json($products);
    }

    /**
     * Remove a product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }
}
