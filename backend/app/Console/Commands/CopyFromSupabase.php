<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Copy the hosted Supabase data into the local MySQL/MariaDB database.
 *
 * Local development runs on XAMPP MariaDB (DB_CONNECTION=mysql), while the
 * original data still lives in Supabase PostgreSQL. This command reads the
 * Supabase rows through the read-only `supabase` connection and re-inserts
 * them into the local database, keeping primary keys so every foreign key
 * (products.user_id, sale_items.sale_id, ...) still points at the right row.
 *
 * Safe to re-run: the target tables are truncated first, and the Supabase
 * rows are never modified.
 *
 *   php artisan db:copy-from-supabase --force
 *
 * Run `php artisan migrate` first so the local schema exists.
 */
class CopyFromSupabase extends Command
{
    protected $signature = 'db:copy-from-supabase
                            {--force : Skip the confirmation prompt}
                            {--chunk=500 : Rows inserted per batch}';

    protected $description = 'Copy Supabase PostgreSQL data into the local MySQL/MariaDB database';

    /**
     * Business tables in foreign-key-safe insert order.
     *
     * Framework tables (migrations, cache, jobs, sessions, failed_jobs) are
     * intentionally skipped: migrations already ran locally, and old sessions
     * or queued jobs must not be resurrected on another environment.
     *
     * @var list<string>
     */
    private array $tables = [
        'users',
        'suppliers',
        'products',
        'sales',
        'sale_items',
        'stock_ins',
        'stock_adjustments',
        'alerts',
        'order_suggestions',
    ];

    public function handle(): int
    {
        $target = config('database.default');

        if ($target === 'supabase') {
            $this->error('The default connection is the read-only source. Point DB_CONNECTION at the MySQL database first.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("This replaces all rows in the local `{$target}` database. Continue?", false)) {
            $this->warn('Aborted. Nothing was changed.');

            return self::FAILURE;
        }

        try {
            DB::connection('supabase')->getPdo();
        } catch (Throwable $e) {
            $this->error('Cannot reach Supabase: '.$e->getMessage());
            $this->line('Check SUPABASE_DB_* in .env.');

            return self::FAILURE;
        }

        $missing = array_filter($this->tables, fn (string $table) => ! Schema::connection($target)->hasTable($table));

        if ($missing !== []) {
            $this->error('Missing local tables: '.implode(', ', $missing).'.');
            $this->line('Run `php artisan migrate` first.');

            return self::FAILURE;
        }

        $this->truncateTarget($target);

        $rows = [];

        foreach ($this->tables as $table) {
            $rows[$table] = $this->copyTable($table, $target);
        }

        $this->newLine();
        $this->table(
            ['Table', 'Rows copied'],
            collect($rows)->map(fn (int $count, string $table) => [$table, $count])->values()->all(),
        );

        $this->newLine();
        $this->info('Supabase data copied to `'.config("database.connections.{$target}.database").'`.');

        return self::SUCCESS;
    }

    private function truncateTarget(string $target): void
    {
        DB::connection($target)->statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach (array_reverse($this->tables) as $table) {
                DB::connection($target)->table($table)->truncate();
            }
        } finally {
            DB::connection($target)->statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function copyTable(string $table, string $target): int
    {
        $sourceColumns = Schema::connection('supabase')->getColumnListing($table);
        $targetColumns = Schema::connection($target)->getColumnListing($table);

        // Copy only the columns both schemas share, so a local migration that
        // adds a column with a default does not break the import.
        $columns = array_values(array_intersect($sourceColumns, $targetColumns));

        if ($columns === []) {
            $this->warn("{$table}: no shared columns, skipped.");

            return 0;
        }

        $chunkSize = max(1, (int) $this->option('chunk'));
        $copied = 0;

        $source = DB::connection('supabase')->table($table);

        if (in_array('id', $columns, true)) {
            $source->orderBy('id');
        }

        $source->chunk($chunkSize, function ($rows) use ($table, $target, $columns, $chunkSize, &$copied) {
            $payload = $rows->map(fn ($row) => $this->normalize((array) $row, $columns))->all();

            DB::connection($target)->table($table)->insert($payload);

            $copied += count($payload);
        });

        if (in_array('id', $columns, true)) {
            $max = DB::connection($target)->table($table)->max('id');

            if ($max !== null) {
                // Keep the next generated id above the copied rows.
                DB::connection($target)->statement(
                    'ALTER TABLE `'.$table.'` AUTO_INCREMENT = '.((int) $max + 1)
                );
            }
        }

        return $copied;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $columns
     * @return array<string, mixed>
     */
    private function normalize(array $row, array $columns): array
    {
        $clean = [];

        foreach ($columns as $column) {
            $value = $row[$column] ?? null;

            // PDO pgsql may hand booleans back as 't'/'f' instead of true/false.
            // MySQL stores BOOLEAN as tinyint, so convert both shapes.
            if ($value === true || $value === 't') {
                $value = 1;
            } elseif ($value === false || $value === 'f') {
                $value = 0;
            } elseif ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d H:i:s');
            }

            $clean[$column] = $value;
        }

        return $clean;
    }
}
