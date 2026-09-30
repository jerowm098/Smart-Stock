<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockIn;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     * SS-35: Automated restocking suggestions based on sales demand.
     *
     * BRD Must: "The system shall generate automated restocking
     * suggestions for Admins based on sales demand."
     *
     * BRD Reliability/Performance: forecasting now runs in the nightly
     * `forecast:orders` job (see App\Services\ForecastingService) which writes
     * pre-computed rows to `order_suggestions`. This endpoint simply reads
     * those rows, so loading the dashboard does not slow the application down.
     *
     * The response shape is preserved for the existing dashboard JavaScript.
     */
    public function restockSuggestions(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $status = (string) $request->query('status', \App\Models\OrderSuggestion::STATUS_ACTIVE);

        $query = \App\Models\OrderSuggestion::with(
            'product:id,name,sku,category,price,current_stock,reorder_threshold'
        )
            ->orderByRaw("CASE urgency WHEN 'critical' THEN 0 WHEN 'low' THEN 1 ELSE 2 END")
            ->orderByDesc('suggested_qty');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Units sold per product inside the BRD's fixed 30-day window, so the
        // overview table can keep showing the "sold in window" column.
        $windowDays = \App\Services\ForecastingService::WINDOW_DAYS;
        $soldMap = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.created_at', '>=', now()->subDays($windowDays - 1)->startOfDay())
            ->groupBy('sale_items.product_id')
            ->select('sale_items.product_id', DB::raw('SUM(sale_items.quantity) as total_qty'))
            ->pluck('total_qty', 'product_id');

        $suggestions = $query->get()->map(function ($s) use ($soldMap) {
            $avgDaily = (float) $s->avg_daily;

            return [
                'id'               => $s->id,
                'product_id'       => $s->product_id,
                'sku'              => $s->product?->sku ?? '—',
                'name'             => $s->product?->name ?? '—',
                'category'         => $s->product?->category ?? '—',
                'current_stock'    => $s->current_stock,
                'reorder_threshold'=> $s->product?->reorder_threshold ?? 0,
                'sold_in_window'   => (int) ($soldMap[$s->product_id] ?? 0),
                'window_days'      => $s->window_days,
                'avg_daily'        => round($avgDaily, 2),
                'reorder_point'    => (float) $s->reorder_point,
                'days_until_out'   => $avgDaily > 0 ? round($s->current_stock / $avgDaily, 1) : null,
                'suggested_qty'    => (int) ceil((float) $s->suggested_qty),
                'urgency'          => $s->urgency,
                'status'           => $s->status,
                'reason'           => $s->reason,
                'generated_at'     => optional($s->generated_at)->format('M d, Y g:i A'),
            ];
        })->values()->all();

        return response()->json([
            'window_days' => \App\Services\ForecastingService::WINDOW_DAYS,
            'cover_days'  => \App\Services\ForecastingService::COVER_DAYS,
            'safety_days' => \App\Services\ForecastingService::SAFETY_DAYS,
            'count'       => count($suggestions),
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * SS-24: Show the dedicated Transaction History page (Admin only).
     * Route: GET /transactions — returns the Blade view.
     */
    public function transactions(): \Illuminate\View\View
    {
        return view('admin.admin-transactions');
    }

    /**
     * SS-24: Paginated transaction history with staff name (Admin only).
     * BRD Must: "Admins to view the transaction history and the staff
     * member who processed them."
     *
     * Supports: ?page= &per_page= &search= &cashier_id= &date_from= &date_to=
     * Returns each sale with receipt no, date, cashier, items, totals.
     */
    public function transactionHistory(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'page'       => 'sometimes|integer|min:1',
            'per_page'   => 'sometimes|integer|min:5|max:100',
            'search'     => 'sometimes|nullable|string|max:100',
            'cashier_id' => 'sometimes|nullable|integer',
            'date_from'  => 'sometimes|nullable|date',
            'date_to'    => 'sometimes|nullable|date',
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);
        $search = trim((string) ($validated['search'] ?? ''));
        $cashierId = $validated['cashier_id'] ?? null;
        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        $query = Sale::with(['cashier:id,name,email', 'items.product:id,name,sku']);

        if ($cashierId) {
            $query->where('user_id', (int) $cashierId);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($search !== '') {
            // Match by receipt no (sale id) or product name/sku inside the sale.
            //
            // NOTE: this previously issued both an ILIKE and a LIKE clause as a
            // "driver fallback". ILIKE is PostgreSQL-only and is a hard syntax
            // error on SQLite, so the duplicate clause is removed. LIKE is
            // case-insensitive for ASCII in PostgreSQL and behaves identically,
            // giving one portable statement instead of two.
            $like = '%' . $search . '%';

            $query->where(function ($q) use ($search, $like) {
                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }

                $q->orWhereHas('items.product', function ($pq) use ($like) {
                    $pq->where('name', 'LIKE', $like)
                        ->orWhere('sku', 'LIKE', $like);
                });
            });
        }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage);

        $data = $paginator->getCollection()->map(function ($sale) {
            $items = $sale->items->map(function ($it) {
                return [
                    'product_name' => $it->product?->name ?? '—',
                    'sku'          => $it->product?->sku ?? '—',
                    'quantity'     => (int) $it->quantity,
                    'unit_price'   => (float) $it->unit_price,
                    'line_total'   => (float) $it->line_total,
                ];
            })->values();

            return [
                'receipt_no'     => 'SS-' . str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT),
                'sale_id'        => $sale->id,
                'date'           => $sale->created_at->format('M d, Y g:i A'),
                'date_raw'       => $sale->created_at->toDateTimeString(),
                'cashier_id'     => $sale->user_id,
                'cashier_name'   => $sale->cashier?->name ?? '—',
                'cashier_email'  => $sale->cashier?->email ?? '—',
                'items_count'    => $items->sum('quantity'),
                'lines_count'    => $items->count(),
                'items'          => $items,
                'total_amount'   => (float) $sale->total_amount,
                'payment_amount' => (float) $sale->payment_amount,
                'change_amount'  => (float) $sale->change_amount,
            ];
        })->values();

        // Cashier filter options: everyone who ever completed a sale.
        $cashiers = \App\Models\User::whereIn('id', Sale::query()->select('user_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json([
            'data'         => $data,
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
            'cashiers'     => $cashiers,
        ]);
    }

    /**
     * SS-25 / SS-34: Downloadable transaction summary (Admin only).
     * BRD Should: "The summary shall be downloadable."
     *
     * Route: GET /api/dashboard/transactions/export?search=&cashier_id=&date_from=&date_to=
     * Returns a CSV file (Excel-compatible) with one row per line item,
     * repeating the receipt header (receipt no, date, cashier, totals).
     * Respects the same filters as transactionHistory() so the Admin can
     * export exactly what is shown on the Transactions page.
     */
    public function exportTransactions(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = Auth::user();
        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        $validated = $request->validate([
            'search'     => 'sometimes|nullable|string|max:100',
            'cashier_id' => 'sometimes|nullable|integer',
            'date_from'  => 'sometimes|nullable|date',
            'date_to'    => 'sometimes|nullable|date',
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $cashierId = $validated['cashier_id'] ?? null;
        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        $query = Sale::with(['cashier:id,name,email', 'items.product:id,name,sku']);

        if ($cashierId) {
            $query->where('user_id', (int) $cashierId);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($search !== '') {
            $like = '%' . $search . '%';

            $query->where(function ($q) use ($search, $like) {
                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }

                $q->orWhereHas('items.product', function ($pq) use ($like) {
                    // Case-insensitive match that works on both PostgreSQL and
                    // SQLite. LIKE is already case-insensitive for ASCII in
                    // PostgreSQL, so this behaves the same as the previous ILIKE
                    // while remaining portable.
                    $pq->where('name', 'LIKE', $like)
                        ->orWhere('sku', 'LIKE', $like);
                });
            });
        }

        // Safety cap so one export cannot exhaust memory.
        $sales = $query->orderByDesc('created_at')->limit(5000)->get();

        $filename = 'smart-stock-transactions-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($sales, $search, $cashierId, $dateFrom, $dateTo) {
            $out = fopen('php://output', 'w');
            // BOM so Excel opens UTF-8 correctly.
            fwrite($out, "\xEF\xBB\xBF");

            // Summary header block.
            fputcsv($out, ['Smart-Stock Transaction Summary']);
            fputcsv($out, ['Exported at', now()->format('M d, Y g:i A')]);
            fputcsv($out, ['Filters', 'search=' . ($search !== '' ? $search : '—')
                . ' | cashier_id=' . ($cashierId ?: 'all')
                . ' | from=' . ($dateFrom ?: '—')
                . ' | to=' . ($dateTo ?: '—')]);
            fputcsv($out, ['Total transactions', $sales->count()]);
            fputcsv($out, ['Grand total', number_format((float) $sales->sum('total_amount'), 2, '.', '')]);
            fputcsv($out, []); // blank line

            // Detail header.
            fputcsv($out, [
                'Receipt No', 'Date', 'Cashier Name', 'Cashier Email',
                'Product', 'SKU', 'Qty', 'Unit Price', 'Line Total',
                'Sale Total', 'Payment', 'Change',
            ]);

            foreach ($sales as $sale) {
                $receiptNo = 'SS-' . str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT);
                $date = $sale->created_at->format('Y-m-d H:i:s');
                $cashierName = $sale->cashier?->name ?? '—';
                $cashierEmail = $sale->cashier?->email ?? '—';

                if ($sale->items->isEmpty()) {
                    fputcsv($out, [
                        $receiptNo, $date, $cashierName, $cashierEmail,
                        '—', '—', 0, '0.00', '0.00',
                        number_format((float) $sale->total_amount, 2, '.', ''),
                        number_format((float) $sale->payment_amount, 2, '.', ''),
                        number_format((float) $sale->change_amount, 2, '.', ''),
                    ]);
                    continue;
                }

                foreach ($sale->items as $it) {
                    fputcsv($out, [
                        $receiptNo,
                        $date,
                        $cashierName,
                        $cashierEmail,
                        $it->product?->name ?? '—',
                        $it->product?->sku ?? '—',
                        (int) $it->quantity,
                        number_format((float) $it->unit_price, 2, '.', ''),
                        number_format((float) $it->line_total, 2, '.', ''),
                        number_format((float) $sale->total_amount, 2, '.', ''),
                        number_format((float) $sale->payment_amount, 2, '.', ''),
                        number_format((float) $sale->change_amount, 2, '.', ''),
                    ]);
                }
            }

            fclose($out);
        };

        return response()->streamDownload($callback, $filename, $headers);
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
            // Kept at 5 to match recentStockIns() so the two side-by-side
            // dashboard panels render the same number of rows.
            ->limit(5)
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
