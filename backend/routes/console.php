<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// SS-39: Regular backups — auto daily 02:00 Asia/Manila, keep newest 7.
// BRD Should: "The system shall create backups regularly."
// Run manually with: php artisan backup:run
// On Render free tier the scheduler needs an external cron hit to /up,
// otherwise use the "Run Backup Now" button on the Admin Backups page.
Schedule::command('backup:run --keep=7')->dailyAt('02:00')->timezone('Asia/Manila');

// BRD (Demand Forecasting & Order Suggestions) Performance:
// "Forecasting calculations shall run asynchronously (e.g., via a scheduled
//  nightly job) to prevent UI lag during regular business hours."
// BRD (Inventory Management) Reliability:
// "The Demand Forecasting algorithm shall run as a nightly scheduled task
//  (or background process) so that loading the suggestions page does not slow
//  down the application."
//
// Runs at 01:00 (before the 02:00 backup) and writes the pre-computed rows the
// Order Suggestions dashboard reads. Run manually with: php artisan forecast:orders
Schedule::command('forecast:orders')->dailyAt('01:00')->timezone('Asia/Manila')
    ->withoutOverlapping();
