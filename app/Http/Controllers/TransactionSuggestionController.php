<?php

namespace App\Http\Controllers;

use App\Models\OrderSuggestion;
use App\Models\Product;
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
     * Render the Order Suggestions dashboard (Admin only).
     */
    public function page()
    {
        return view('order-suggestions');
    }

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
            'generated_at'     => optional($s->generated_at)->format('M d, Y g:i A'),
            'actioned_at'      => optional($s->actioned_at)->format('M d, Y g:i A'),
        ];
    }
}
