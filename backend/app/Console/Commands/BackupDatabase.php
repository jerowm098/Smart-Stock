<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SS-39: Regular database backups (Supabase PostgreSQL only).
 *
 * BRD Should: "The system shall create backups regularly."
 *
 * - Primary: Supabase PostgreSQL (pgsql) -> portable JSON dump.
 * - Keeps only the newest --keep files (default 7).
 * - Safe to run via scheduler (daily 02:00) or manually:
 *     php artisan backup:run
 *     php artisan backup:run --keep=14
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:run {--keep=7 : How many newest backups to keep}';

    protected $description = 'Create a timestamped Supabase PostgreSQL backup in storage/app/private/backups';

    public function handle(): int
    {
        $dir = storage_path('app/private/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $stamp = now()->format('Ymd-His');
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver", $connection);

        // Supabase PostgreSQL only: portable JSON dump (no doctrine/dbal needed).
        $filename = "smart-stock-backup-{$stamp}.json";
        $tables = [
            'users', 'products', 'suppliers', 'sales', 'sale_items',
            'stock_ins', 'stock_adjustments', 'alerts',
            'cache', 'jobs', 'migrations',
        ];
        $tables = array_values(array_filter($tables, fn ($t) => Schema::hasTable($t)));

        $dump = [
            'app' => config('app.name'),
            'connection' => $connection,
            'driver' => $driver,
            'exported_at' => now()->toDateTimeString(),
            'tables' => [],
        ];
        foreach ($tables as $table) {
            $dump['tables'][$table] = DB::table($table)->get()->toArray();
        }

        file_put_contents(
            $dir . DIRECTORY_SEPARATOR . $filename,
            json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        $this->info("PostgreSQL backup created: {$filename} (" . count($tables) . ' tables)');

        $this->prune((int) $this->option('keep'), $dir);

        return self::SUCCESS;
    }

    protected function prune(int $keep, string $dir): void
    {
        $keep = max(1, $keep);
        $files = glob($dir . DIRECTORY_SEPARATOR . 'smart-stock-backup-*') ?: [];
        // Newest last so we can slice off the tail to keep.
        sort($files);
        $excess = count($files) - $keep;
        for ($i = 0; $i < $excess; $i++) {
            @unlink($files[$i]);
            $this->info('Pruned old backup: ' . basename($files[$i]));
        }
    }
}
