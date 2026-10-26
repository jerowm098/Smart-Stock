<?php

namespace App\Console\Commands;

use App\Services\ForecastingService;
use Illuminate\Console\Command;

/**
 * BRD (Demand Forecasting & Order Suggestions) — nightly forecasting job.
 *
 * Performance: "Forecasting calculations shall run asynchronously (e.g., via a
 * scheduled nightly job) to prevent UI lag during regular business hours."
 * Reliability: "The Demand Forecasting algorithm shall run as a nightly
 * scheduled task (or background process) so that loading the suggestions page
 * does not slow down the application." (BRD — Inventory Management)
 *
 * Scheduled in routes/console.php at 01:00 Asia/Manila, and can be triggered
 * on demand:
 *
 *     php artisan forecast:orders
 */
class ForecastOrders extends Command
{
    protected $signature = 'forecast:orders';

    protected $description = 'Recompute demand-forecasting order suggestions (BRD: nightly scheduled task)';

    public function handle(ForecastingService $forecasting): int
    {
        $this->info('Computing demand forecasts…');

        $stats = $forecasting->generate();

        $this->line(sprintf(
            '  created: %d | updated: %d | preserved (already actioned): %d | static-fallback items: %d',
            $stats['created'],
            $stats['updated'],
            $stats['preserved'],
            $stats['skipped_fallback']
        ));

        $this->info('Order suggestions updated.');

        return self::SUCCESS;
    }
}
