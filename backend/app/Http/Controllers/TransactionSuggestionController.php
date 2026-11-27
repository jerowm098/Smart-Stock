<?php

namespace App\Http\Controllers;

use App\Models\OrderSuggestion;
use App\Models\Product;
use App\Services\ActivityLogger;
use App\Services\ForecastingService;
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

        $status = (string) $request->query('status', OrderSuggestion::STATUS_ACTIVE);

        $query = OrderSuggestion::with('product:id,name,sku,category,price,current_stock,reorder_threshold')
            ->orderByRaw("CASE urgency WHEN 'critical' THEN 0 WHEN 'low' THEN 1 ELSE 2 END")
            ->orderByDesc('suggested_qty');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $filename = 'order-suggestions-'.now()->format('Ymd-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
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
        $products = \App\Models\Product::orderBy('id')->limit(7)->get();

        if ($products->isEmpty()) {
            $fallbackItems = [
                ['name' => 'Claw Hammer 16oz', 'sku' => 'CLA-HAM-16O', 'category' => 'Hand Tools', 'price' => 245, 'current_stock' => 2, 'reorder_threshold' => 10, 'receiving_unit' => 'piece'],
                ['name' => 'Portland Cement 40kg', 'sku' => 'POR-CEM-40K', 'category' => 'Building Materials', 'price' => 260, 'current_stock' => 5, 'reorder_threshold' => 15, 'receiving_unit' => 'sack'],
                ['name' => 'Latex Paint White 4L', 'sku' => 'LAT-PAI-WHI', 'category' => 'Paints & Coatings', 'price' => 680, 'current_stock' => 3, 'reorder_threshold' => 8, 'receiving_unit' => 'gallon'],
                ['name' => 'PVC Pipe 1/2in 3m', 'sku' => 'PVC-PIP-12I', 'category' => 'Plumbing', 'price' => 95, 'current_stock' => 6, 'reorder_threshold' => 12, 'receiving_unit' => 'piece'],
                ['name' => 'THHN Wire 12 AWG', 'sku' => 'THH-WIR-12A', 'category' => 'Electrical', 'price' => 38, 'current_stock' => 14, 'reorder_threshold' => 25, 'receiving_unit' => 'meter'],
                ['name' => 'Common Wire Nails 2in', 'sku' => 'COM-WIR-NAI', 'category' => 'Fasteners', 'price' => 85, 'current_stock' => 4, 'reorder_threshold' => 10, 'receiving_unit' => 'kilogram'],
                ['name' => 'Heavy Duty Padlock 50mm', 'sku' => 'HEA-DUT-PAD', 'category' => 'Hardware', 'price' => 190, 'current_stock' => 5, 'reorder_threshold' => 8, 'receiving_unit' => 'piece'],
            ];

            $createdProducts = collect();
            foreach ($fallbackItems as $item) {
                $sku = $item['sku'];
                $suffix = 2;
                while (\App\Models\Product::where('sku', $sku)->exists()) {
                    $sku = $item['sku'] . '-' . $suffix;
                    $suffix++;
                }
                $createdProducts->push(\App\Models\Product::create([
                    ...$item,
                    'sku' => $sku,
                    'is_active' => true,
                ]));
            }
            $products = $createdProducts;
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
        ], 201);
    }

    /**
     * Delete all order suggestions.
     */
    public function destroyAll(): JsonResponse
    {
        $count = OrderSuggestion::count();
        if ($count === 0) {
            return response()->json(['message' => 'No order suggestions to delete.']);
        }

        OrderSuggestion::query()->delete();

        return response()->json([
            'message' => "All order suggestions deleted ({$count}).",
        ]);
    }
}
