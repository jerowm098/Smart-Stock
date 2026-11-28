<?php

namespace App\Http\Controllers;

use App\Models\OrderSuggestion;
use App\Models\Product;
use App\Services\ActivityLogger;
use App\Services\ForecastingService;
use App\Services\SkuGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * BRD (Demand Forecasting & Order Suggestions) — the "Order Suggestions" dashboard.
 *
 * Scope implemented here:
 *   - Generation of a "Suggested Orders" dashboard for the Admin
 *   - Displaying recommended restock quantities
 *   - Ability for the Admin to dismiss a suggestion or mark it as "Ordered"
 *   - Exporting the suggested order list to CSV
 *
 * BRD Security: "Any attempt to access the suggestion export endpoint without a
 * valid Admin session token shall result in a 403 Forbidden response." This is
 * enforced by the EnsureUserIsAdmin middleware registered on every route below.
 */
class TransactionSuggestionController extends Controller
{
    public function __construct(private ForecastingService $forecasting) {}

    /**
     * List suggestions. Defaults to the active queue the Admin still has to act
     * on; ?status=all|ordered|dismissed shows the full lifecycle history.
     */
    public function index(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $status = (string) $request->query('status', OrderSuggestion::STATUS_ACTIVE);

        $query = OrderSuggestion::with('product:id,name,sku,category,price,current_stock,reorder_threshold')
            ->orderByRaw("CASE urgency WHEN 'critical' THEN 0 WHEN 'low' THEN 1 ELSE 2 END")
            ->orderByDesc('suggested_qty');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $suggestions = $query->get()->map(fn (OrderSuggestion $s) => $this->present($s));

        return response()->json([
            'status'       => $status,
            'count'        => $suggestions->count(),
            'suggestions'  => $suggestions,
            'formulas'     => [
                'window_days'  => ForecastingService::WINDOW_DAYS,
                'safety_days'  => ForecastingService::SAFETY_DAYS,
                'cover_days'   => ForecastingService::COVER_DAYS,
                'fallback_threshold' => ForecastingService::STATIC_FALLBACK_THRESHOLD,
            ],
        ]);
    }

    /**
     * BRD: "The system shall allow the user to mark a suggestion as 'Ordered'."
     */
    public function markOrdered(Request $request, int $id): JsonResponse
    {
        return $this->action($request, $id, OrderSuggestion::STATUS_ORDERED);
    }

    /**
     * BRD: "The system shall allow the user to mark a suggestion as 'Dismissed'."
     */
    public function dismiss(Request $request, int $id): JsonResponse
    {
        return $this->action($request, $id, OrderSuggestion::STATUS_DISMISSED);
    }

    /**
     * BRD: "The system shall allow the user to export the active Order
     * Suggestions list as a printable format or spreadsheet."
     */
    public function export(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $validated = $request->validate([
            'status'    => 'sometimes|nullable|in:active,ordered,dismissed,all',
            'urgency'   => 'sometimes|nullable|in:critical,low',
            'category'  => 'sometimes|nullable|string|max:100',
            'date_from' => 'sometimes|nullable|date',
            'date_to'   => 'sometimes|nullable|date',
        ]);

        $status   = (string) ($validated['status'] ?? OrderSuggestion::STATUS_ACTIVE);
        $urgency  = $validated['urgency'] ?? null;
        $category = isset($validated['category']) ? trim((string) $validated['category']) : '';
        $dateFrom = $validated['date_from'] ?? null;
        $dateTo   = $validated['date_to'] ?? null;

        $query = OrderSuggestion::with('product:id,name,sku,category,price,current_stock,reorder_threshold')
            ->orderByRaw("CASE urgency WHEN 'critical' THEN 0 WHEN 'low' THEN 1 ELSE 2 END")
            ->orderByDesc('suggested_qty');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($urgency === 'critical' || $urgency === 'low') {
            $query->where('urgency', $urgency);
        }

        if ($category !== '') {
            $query->whereHas('product', fn ($q) => $q->where('category', $category));
        }

        if ($dateFrom) {
            $query->whereDate(\Illuminate\Support\Facades\DB::raw('COALESCE(generated_at, created_at)'), '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate(\Illuminate\Support\Facades\DB::raw('COALESCE(generated_at, created_at)'), '<=', $dateTo);
        }

        $parts = ['smart-stock-order-suggestions'];
        if ($status !== 'all') {
            $parts[] = $status;
        }
        if ($urgency) {
            $parts[] = $urgency;
        }
        if ($dateFrom && $dateTo) {
            $parts[] = $dateFrom === $dateTo ? $dateFrom : ($dateFrom . '-to-' . $dateTo);
        } elseif ($dateFrom) {
            $parts[] = 'from-' . $dateFrom;
        } elseif ($dateTo) {
            $parts[] = 'until-' . $dateTo;
        } else {
            $parts[] = now()->format('Y-m-d_His');
        }

        $filename = implode('-', $parts) . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($query) {
            $out = fopen('php://output', 'w');

            // BOM so Excel renders UTF-8 correctly.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'SKU', 'Item Name', 'Category', 'Current Stock',
                'Avg Daily Sales', 'Reorder Point', 'Suggested Order Qty',
                'Urgency', 'Status', 'Reason',
            ]);

            foreach ($query->get() as $s) {
                fputcsv($out, [
                    $s->product?->sku ?? '—',
                    $s->product?->name ?? '—',
                    $s->product?->category ?? '—',
                    $s->current_stock,
                    number_format((float) $s->avg_daily, 2),
                    number_format((float) $s->reorder_point, 2),
                    (int) ceil((float) $s->suggested_qty),
                    $s->urgency,
                    $s->status,
                    $s->reason ?? '',
                ]);
            }

            fclose($out);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    /**
     * BRD Performance: let the Admin trigger the same nightly job on demand.
     */
    public function runForecast(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $stats = $this->forecasting->generate();

        return response()->json([
            'message' => 'Forecasts recomputed.',
            'stats'   => $stats,
        ]);
    }

    /**
     * Shared state transition for ordered/dismissed.
     */
    private function action(Request $request, int $id, string $status): JsonResponse
    {
        $suggestion = OrderSuggestion::with('product:id,name,sku')->find($id);

        if (! $suggestion) {
            return response()->json(['message' => 'Suggestion not found.'], 404);
        }

        if ($suggestion->status !== OrderSuggestion::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'This suggestion has already been actioned.',
            ], 409);
        }

        $suggestion->update([
            'status'      => $status,
            'actioned_at' => now(),
            'actioned_by' => $request->user()?->id,
        ]);

        $user = $request->user();
        if ($user) {
            $fresh = $suggestion->fresh(['product']);
            ActivityLogger::safe(function () use ($user, $fresh, $status) {
                if ($status === OrderSuggestion::STATUS_ORDERED) {
                    ActivityLogger::suggestionOrdered($user, $fresh);
                } else {
                    ActivityLogger::suggestionDismissed($user, $fresh);
                }
            });
        }

        return response()->json([
            'message'    => $status === OrderSuggestion::STATUS_ORDERED
                ? 'Suggestion marked as ordered.'
                : 'Suggestion dismissed.',
            'suggestion' => $this->present($suggestion->fresh()),
        ]);
    }

    /**
     * Shape a suggestion for the dashboard, enriched with product details.
     */
    private function present(OrderSuggestion $s): array
    {
        $avgDaily = (float) $s->avg_daily;

        return [
            'id'               => $s->id,
            'product_id'       => $s->product_id,
            'sku'              => $s->product?->sku ?? '—',
            'name'             => $s->product?->name ?? '—',
            'category'         => $s->product?->category ?? '—',
            'unit_price'       => $s->product?->price ?? null,
            'current_stock'    => $s->current_stock,
            'reorder_point'    => (float) $s->reorder_point,
            'avg_daily'        => round($avgDaily, 2),
            'suggested_qty'    => (int) ceil((float) $s->suggested_qty),
            'est_cost'         => $s->product?->price !== null
                ? round((float) $s->product->price * (float) $s->suggested_qty, 2)
                : null,
            'days_until_out'   => $avgDaily > 0 ? round($s->current_stock / $avgDaily, 1) : null,
            'urgency'          => $s->urgency,
            'status'           => $s->status,
            'reason'           => $s->reason,
            'generated_at'     => optional($s->generated_at)->diffForHumans(),
            'actioned_at'      => optional($s->actioned_at)->diffForHumans(),
        ];
    }

    /**
     * Seed 7 sample active order suggestions for quick pagination / table testing.
     */
    public function seed(): JsonResponse
    {
        $user = Auth::user();
        $products = \App\Models\Product::orderBy('id')->limit(7)->get();
        $createdProductsCount = 0;
        $createdCategoriesCount = 0;
        $createdSuppliersCount = 0;
        $createdSalesCount = 0;

        if ($products->isEmpty()) {
            // Ensure active suppliers exist so seeded items link cleanly to Stock Inventory.
            $suppliers = \App\Models\Supplier::active()->orderBy('id')->get();
            if ($suppliers->isEmpty()) {
                $defaultSuppliers = [
                    ['name' => 'Metro Hardware Trading', 'contact_person' => 'Ramon Reyes', 'phone' => '0917-555-0101', 'email' => 'sales@metrohardware.ph'],
                    ['name' => 'BuildRight Supply Corp.', 'contact_person' => 'Elena Cruz', 'phone' => '0918-555-0102', 'email' => 'orders@buildright.ph'],
                    ['name' => 'PrimeCoat & Electrical Co.', 'contact_person' => 'Marco Santos', 'phone' => '0919-555-0103', 'email' => 'dispatch@primecoat.ph'],
                    ['name' => 'FlowLine Plumbing Depot', 'contact_person' => 'Liza Mendoza', 'phone' => '0920-555-0104', 'email' => 'supply@flowline.ph'],
                    ['name' => 'VoltEdge Electrical Supplies', 'contact_person' => 'Carlos Villanueva', 'phone' => '0921-555-0105', 'email' => 'sales@voltedge.ph'],
                    ['name' => 'SteelGrip Fasteners Inc.', 'contact_person' => 'Grace Bautista', 'phone' => '0922-555-0106', 'email' => 'orders@steelgrip.ph'],
                    ['name' => 'Apex Power Tools Distribution', 'contact_person' => 'Dennis Aquino', 'phone' => '0923-555-0107', 'email' => 'support@apexpowertools.ph'],
                ];
                foreach ($defaultSuppliers as $row) {
                    $existingSup = \App\Models\Supplier::where('name', $row['name'])->first();
                    if ($existingSup) {
                        if (! $existingSup->is_active) {
                            $existingSup->update($row + ['is_active' => true]);
                            $createdSuppliersCount++;
                        }
                    } else {
                        \App\Models\Supplier::create($row + ['is_active' => true]);
                        $createdSuppliersCount++;
                    }
                }
                $suppliers = \App\Models\Supplier::active()->orderBy('id')->get();
            }

            // Match InventoryController::seed() exactly: 3 In stock, 2 Low stock, 2 Critical/Out of stock.
            $fallbackItems = [
                ['name' => 'Claw Hammer 16oz', 'category' => 'Hand Tools', 'category_desc' => 'Manual carpentry and workshop hand tools.', 'price' => 245, 'current_stock' => 24, 'reorder_threshold' => 10, 'receiving_unit' => 'piece', 'note' => 'Received — DR #1041'],
                ['name' => 'Portland Cement 40kg', 'category' => 'Building Materials', 'category_desc' => 'Cement, lumber, aggregates, and structural supplies.', 'price' => 260, 'current_stock' => 45, 'reorder_threshold' => 15, 'receiving_unit' => 'sack', 'note' => 'Supplier delivery — verified'],
                ['name' => 'Latex Paint White 4L', 'category' => 'Paints & Coatings', 'category_desc' => 'Interior and exterior paints, primers, rollers, and brushes.', 'price' => 680, 'current_stock' => 4, 'reorder_threshold' => 8, 'receiving_unit' => 'gallon', 'note' => 'Received — batch checked'],
                ['name' => 'PVC Pipe 1/2in 3m', 'category' => 'Plumbing', 'category_desc' => 'PVC pipes, fittings, valves, and sealants.', 'price' => 95, 'current_stock' => 0, 'reorder_threshold' => 12, 'receiving_unit' => 'piece', 'note' => 'Initial stock entry — awaiting restock'],
                ['name' => 'THHN Wire 12 AWG', 'category' => 'Electrical', 'category_desc' => 'Wires, conduits, switches, breakers, and lighting.', 'price' => 38, 'current_stock' => 80, 'reorder_threshold' => 25, 'receiving_unit' => 'meter', 'note' => 'Supplier delivery — DR #1045'],
                ['name' => 'Common Wire Nails 2in', 'category' => 'Fasteners', 'category_desc' => 'Nails, screws, bolts, anchors, and washers.', 'price' => 85, 'current_stock' => 0, 'reorder_threshold' => 10, 'receiving_unit' => 'kilogram', 'note' => 'Initial stock entry — awaiting restock'],
                ['name' => 'Cordless Drill 18V', 'category' => 'Power Tools', 'category_desc' => 'Cordless drills, grinders, saws, and electric equipment.', 'price' => 1850, 'current_stock' => 3, 'reorder_threshold' => 5, 'receiving_unit' => 'piece', 'note' => 'Received — warranty sealed'],
            ];

            $createdProducts = collect();
            foreach ($fallbackItems as $index => $item) {
                $cat = \App\Models\Category::whereRaw('LOWER(TRIM(name)) = LOWER(TRIM(?))', [$item['category']])->first();
                if (! $cat) {
                    $cat = \App\Models\Category::create([
                        'name' => $item['category'],
                        'description' => $item['category_desc'],
                    ]);
                    $createdCategoriesCount++;
                }

                $product = \App\Models\Product::create([
                    'name' => $item['name'],
                    'sku' => SkuGenerator::next($item['name']),
                    'category' => $item['category'],
                    'category_id' => $cat->id,
                    'price' => $item['price'],
                    'current_stock' => $item['current_stock'],
                    'reorder_threshold' => $item['reorder_threshold'],
                    'receiving_unit' => $item['receiving_unit'],
                    'is_active' => true,
                    'is_listed' => true,
                    'is_queue_dismissed' => false,
                ]);

                if ($suppliers->isNotEmpty() && $user) {
                    $sup = $suppliers[$index % $suppliers->count()];
                    \App\Models\StockIn::create([
                        'product_id' => $product->id,
                        'user_id' => $user->id,
                        'supplier_id' => $sup->id,
                        'quantity_received' => $item['current_stock'],
                        'unit_of_measure' => $item['receiving_unit'],
                        'unit_conversion' => 1,
                        'piece_delta' => $item['current_stock'],
                        'stock_before' => 0,
                        'stock_after' => $item['current_stock'],
                        'note' => $item['note'] ?? 'Received',
                    ]);
                }

                $createdProducts->push($product);
                $createdProductsCount++;
            }
            $products = $createdProducts;
        }

        // Seed connected sample transactions when none exist so Transactions,
        // Overview Revenue Trend, and Forecasting all have real sales history.
        if (\App\Models\Sale::count() === 0 && $products->isNotEmpty() && $user) {
            $cashier = \App\Models\User::where('role', 'cashier')->first();
            for ($i = 0; $i < 7; $i++) {
                $product = $products[$i % $products->count()];
                $qty = ($i % 3) + 2;
                $unitPrice = (float) ($product->price ?: 100);
                $lineTotal = round($unitPrice * $qty, 2);
                $payment = ceil($lineTotal / 50) * 50;
                $change = round($payment - $lineTotal, 2);
                $useCashier = $cashier && ($i % 2 === 1);
                $ts = now()->subDays(6 - $i)->subMinutes(15);

                $sale = \App\Models\Sale::create([
                    'user_id' => $useCashier ? $cashier->id : $user->id,
                    'total_amount' => $lineTotal,
                    'payment_amount' => $payment,
                    'change_amount' => $change,
                    'channel' => $useCashier ? 'cashier' : 'admin',
                    'created_at' => $ts,
                    'updated_at' => $ts,
                ]);

                \App\Models\SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);
                $createdSalesCount++;
            }
        }

        $presets = [
            ['urgency' => 'critical', 'avg_daily' => 4.2, 'reorder_point' => 15, 'suggested_qty' => 30, 'reason' => 'Critical: under 2 days of supply left at current sales velocity.'],
            ['urgency' => 'critical', 'avg_daily' => 3.5, 'reorder_point' => 14, 'suggested_qty' => 25, 'reason' => 'Critical: projected stockout within 3 days at current demand.'],
            ['urgency' => 'critical', 'avg_daily' => 2.8, 'reorder_point' => 12, 'suggested_qty' => 20, 'reason' => 'Critical: stock is at or below the configured reorder threshold.'],
            ['urgency' => 'low', 'avg_daily' => 2.1, 'reorder_point' => 10, 'suggested_qty' => 18, 'reason' => 'Stock is below the 7-day reorder point based on recent POS demand.'],
            ['urgency' => 'low', 'avg_daily' => 1.6, 'reorder_point' => 10, 'suggested_qty' => 15, 'reason' => 'Below safety stock buffer; suggested top-up restores 30-day supply.'],
            ['urgency' => 'low', 'avg_daily' => 1.1, 'reorder_point' => 8, 'suggested_qty' => 12, 'reason' => 'Moderate sales pace; restock recommended before next week.'],
            ['urgency' => 'low', 'avg_daily' => 0.7, 'reorder_point' => 6, 'suggested_qty' => 10, 'reason' => 'Flagged below reorder point; top-up restores 30-day coverage.'],
        ];

        $created = 0;
        foreach ($presets as $i => $preset) {
            $product = $products[$i % $products->count()];
            OrderSuggestion::create([
                'product_id' => $product->id,
                'status' => OrderSuggestion::STATUS_ACTIVE,
                'avg_daily' => $preset['avg_daily'],
                'reorder_point' => $preset['reorder_point'],
                'suggested_qty' => $preset['suggested_qty'],
                'current_stock' => (int) $product->current_stock,
                'window_days' => 30,
                'urgency' => $preset['urgency'],
                'reason' => $preset['reason'],
                'generated_at' => now()->subMinutes((7 - $i) * 5),
            ]);
            $created++;
        }

        return response()->json([
            'message' => "{$created} sample order suggestions added.",
            'created' => $created,
            'created_products' => $createdProductsCount,
            'created_sales' => $createdSalesCount,
            'created_categories' => $createdCategoriesCount,
            'created_suppliers' => $createdSuppliersCount,
        ], 201);
    }

    /**
     * Delete all order suggestions.
     */
    public function destroyAll(): JsonResponse
    {
        $count = OrderSuggestion::count();
        if ($count === 0) {
            return response()->json(['message' => 'No order suggestions to delete.', 'deleted' => 0]);
        }

        OrderSuggestion::query()->delete();

        return response()->json([
            'message' => "All order suggestions deleted ({$count}).",
            'deleted' => $count,
        ]);
    }
}
