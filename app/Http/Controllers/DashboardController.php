<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockIn;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dashboard API: aggregate stats for the 4 stat cards.
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Total products (admin sees all)
        $totalProducts = Product::count();

        // Total revenue from all sales
        $totalRevenue = (float) Sale::sum('total_amount');

        // Low stock count (current_stock <= reorder_threshold)
        $lowStockCount = Product::whereColumn('current_stock', '<=', 'reorder_threshold')->count();

        // Total number of transactions
        $totalSales = Sale::count();

        // Active suppliers (for admin overview)
        $supplierCount = Supplier::count();

        return response()->json([
            'total_products'   => $totalProducts,
            'total_revenue'    => $totalRevenue,
            'low_stock_count'  => $lowStockCount,
            'total_sales'      => $totalSales,
            'supplier_count'   => $supplierCount,
        ]);
    }

    /**
     * Dashboard API: total revenue grouped by date for the line chart.
     * Returns last 30 days of daily revenue.
     */
    public function revenueChart(): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $days = 30;

        // Build a date range for the labels
        $labels = [];
        $values = [];
        $revenueMap = [];

        // Get aggregated revenue per day
        $rows = Sale::select(
                DB::raw("DATE(created_at) as date"),
                DB::raw("SUM(total_amount) as total")
            )
            ->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->groupBy(DB::raw("DATE(created_at)"))
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date');

        // Fill in every day in the range so chart is continuous
        for ($i = $days - 1; $i >= 0; $i--) {
            $dateKey = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('M d');
            $values[] = (float) ($rows[$dateKey] ?? 0);
        }

        return response()->json([
            'labels' => $labels,
            'values' => $values,
        ]);
    }

    /**
     * Dashboard API: top selling products ranked by total quantity sold.
     */
    public function topProducts(): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $products = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('SUM(sale_items.quantity) as total_qty'),
                DB::raw('SUM(sale_items.line_total) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return response()->json($products);
    }

    /**
     * Dashboard API: recent stock-in transactions.
     */
    public function recentStockIns(): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $stockIns = StockIn::with(['product', 'supplier', 'staff'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($si) {
                return [
                    'id'              => $si->id,
                    'product_name'    => $si->product?->name ?? '—',
                    'supplier_name'   => $si->supplier?->name ?? '—',
                    'quantity'        => $si->quantity_received,
                    'unit'            => $si->unit_of_measure,
                    'staff_name'      => $si->staff?->name ?? '—',
                    'date'            => $si->created_at->format('M d, Y g:i A'),
                ];
            });

        return response()->json($stockIns);
    }

    /**
     * Dashboard API: recent sales activity (sale items with product details).
     */
    public function recentSales(): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $sales = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->select(
                'sale_items.id',
                'products.name as product_name',
                'sale_items.quantity',
                'sale_items.unit_price',
                'sale_items.line_total',
                'sales.created_at'
            )
            ->orderByDesc('sales.created_at')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id'           => $item->id,
                    'product_name' => $item->product_name ?? '—',
                    'quantity'     => $item->quantity,
                    'unit_price'   => $item->unit_price,
                    'line_total'   => $item->line_total,
                    'date'         => \Carbon\Carbon::parse($item->created_at)->format('M d, Y g:i A'),
                ];
            });

        return response()->json($sales);
    }
}
