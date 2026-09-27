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
