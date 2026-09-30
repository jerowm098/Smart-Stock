<?php

namespace App\Services;

use App\Models\OrderSuggestion;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * BRD (Demand Forecasting & Order Suggestions) — the forecasting engine.
 *
 * The BRD states the business rules verbatim:
 *
 *   Daily Velocity           = Total Units Sold / 30 days
 *   Reorder Point (Threshold)= Daily Velocity * 7 days
 *   Suggested Order Quantity = (Daily Velocity * 30 days) - Current Stock
 *   If Current Stock <= Reorder Point  ->  Add to Suggestions List
 *
 * Reliability rule: "If an item has less than 7 days of sales history, the
 * system shall bypass the forecasting algorithm and rely on a static minimum
 * threshold (e.g., 10 units)."
 *
 * Performance rule: "Forecasting calculations shall run asynchronously (e.g.,
 * via a scheduled nightly job) to prevent UI lag during regular business hours."
 * That job is `php artisan forecast:orders`, scheduled nightly in
 * routes/console.php; the dashboard only reads the pre-computed rows.
 */
class ForecastingService
{
    /** BRD: velocity and target supply are both measured over 30 days. */
    public const WINDOW_DAYS = 30;

    /** BRD: reorder point is 7 days of demand. */
    public const SAFETY_DAYS = 7;

    /** BRD: suggested order restores the item to a 30-day supply. */
    public const COVER_DAYS = 30;

    /**
     * BRD fallback: items with fewer than 7 days of sales history skip the
     * forecast and fall back to this static threshold.
     */
    public const STATIC_FALLBACK_THRESHOLD = 10;

    /** An item needs at least this many days of recorded sales to be forecast. */
    public const MIN_HISTORY_DAYS = 7;

    /**
     * Compute suggestions and persist them.
     *
     * Rows the Admin already actioned (ordered / dismissed) are preserved so
     * their decisions survive re-computation, per the BRD's
     * "Suggestion Record Lifecycle" state model. Only `active` rows are refreshed.
     *
     * @return array{created:int,updated:int,preserved:int,skipped_fallback:int}
     */
    public function generate(): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'preserved' => 0, 'skipped_fallback' => 0];

        $now = now();
        $windowStart = $now->copy()->subDays(self::WINDOW_DAYS - 1)->startOfDay();

        // Units sold per product inside the 30-day demand window.
        $soldMap = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.created_at', '>=', $windowStart)
            ->groupBy('sale_items.product_id')
            ->select('sale_items.product_id', DB::raw('SUM(sale_items.quantity) as total_qty'))
            ->pluck('total_qty', 'product_id');

        // How many distinct days each product actually sold on, so we can apply
        // the BRD's "<7 days of history" bypass rule.
        $historyDaysMap = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.created_at', '>=', $windowStart)
            ->where('sales.created_at', '>=', $now->copy()->subDays(self::MIN_HISTORY_DAYS - 1)->startOfDay())
            ->groupBy('sale_items.product_id')
            ->select('sale_items.product_id', DB::raw('COUNT(DISTINCT DATE(sales.created_at)) as days'))
            ->pluck('days', 'product_id');

        $products = Product::where('is_active', true)->orderBy('name')->get();

        foreach ($products as $product) {
            $sold        = (int) ($soldMap[$product->id] ?? 0);
            $historyDays = (int) ($historyDaysMap[$product->id] ?? 0);
            $current     = (int) $product->current_stock;
            $threshold   = (int) $product->reorder_threshold;

            // --- BRD Reliability: bypass forecasting on thin history ---
            if ($historyDays < self::MIN_HISTORY_DAYS) {
                $fallbackThreshold = max(self::STATIC_FALLBACK_THRESHOLD, $threshold);
                $reorderPoint      = $fallbackThreshold;
                $suggestedQty      = max(0, $fallbackThreshold - $current);
                $avgDaily          = 0.0;
                $usesFallback      = true;
                $stats['skipped_fallback']++;
            } else {
                // --- BRD business rules ---
                $avgDaily     = $sold / self::WINDOW_DAYS;
                $reorderPoint = $avgDaily * self::SAFETY_DAYS;
                $suggestedQty = max(0, ($avgDaily * self::COVER_DAYS) - $current);
                $usesFallback = false;
            }

            // BRD: "If Current Stock <= Reorder Point -> Add to Suggestions List."
            $isFlagged = $current <= $reorderPoint;

            $existing = OrderSuggestion::where('product_id', $product->id)->first();

            // Preserve an Admin decision; only refresh still-active rows.
            if ($existing && $existing->status !== 'active') {
                $stats['preserved']++;
                continue;
            }

            if (! $isFlagged) {
                // No longer needs reordering — drop any stale active suggestion.
                if ($existing) {
                    $existing->delete();
                }
                continue;
            }

            $daysLeft = $avgDaily > 0 ? round($current / $avgDaily, 1) : null;

            $urgency = 'watch';
            if ($current <= 0) {
                $urgency = 'critical';
            } elseif ($usesFallback) {
                $urgency = $current <= $threshold ? 'critical' : 'low';
            } elseif ($daysLeft !== null && $daysLeft <= 3) {
                // BRD Usability: "clearly differentiate critically low items
                // (e.g., under 3 days of supply) using distinct visual indicators."
                $urgency = 'critical';
            } elseif ($isFlagged) {
                $urgency = 'low';
            }

            $reason = $this->buildReason($usesFallback, $historyDays, $sold, $current, $avgDaily, $reorderPoint, $daysLeft);

            $payload = [
                'status'       => 'active',
                'avg_daily'    => round($avgDaily, 2),
                'reorder_point'=> round($reorderPoint, 2),
                'suggested_qty'=> (int) ceil($suggestedQty),
                'current_stock'=> $current,
                'window_days'  => self::WINDOW_DAYS,
                'urgency'      => $urgency,
                'reason'       => $reason,
                'generated_at' => $now,
            ];

            if ($existing) {
                $existing->update($payload);
                $stats['updated']++;
            } else {
                OrderSuggestion::create($payload + ['product_id' => $product->id]);
                $stats['created']++;
            }
        }

        return $stats;
    }

    /**
     * Human-readable justification shown next to each suggestion.
     */
    private function buildReason(
        bool $usesFallback,
        int $historyDays,
        int $sold,
        int $current,
        float $avgDaily,
        float $reorderPoint,
        ?float $daysLeft
    ): string {
        if ($usesFallback) {
            return sprintf(
                'Less than %d days of sales history (%d day%s recorded) — using the static %d-unit minimum threshold instead of the forecast.',
                self::MIN_HISTORY_DAYS,
                $historyDays,
                $historyDays === 1 ? '' : 's',
                self::STATIC_FALLBACK_THRESHOLD
            );
        }

        if ($current <= 0) {
            return sprintf('Out of stock. Sold %d pcs in the last %d days.', $sold, self::WINDOW_DAYS);
        }

        if ($daysLeft !== null && $daysLeft <= 3) {
            return sprintf(
                'Critical: only ~%s days of supply left at %.2f pcs/day. Reorder point is %.1f.',
                $daysLeft,
                $avgDaily,
                $reorderPoint
            );
        }

        return sprintf(
            'Stock %d is at/below the reorder point of %.1f (%d-day demand). Suggested top-up restores a %d-day supply.',
            $current,
            $reorderPoint,
            self::SAFETY_DAYS,
            self::COVER_DAYS
        );
    }
}
