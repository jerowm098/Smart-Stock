<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SMART-STOCK now runs on a SINGLE shared inventory database.
     *
     * Previously every account had its own private product catalogue: the
     * application filtered products by `user_id` and the schema enforced a
     * composite UNIQUE (user_id, sku). That meant an Admin and a Cashier
     * logged into two different datasets for the same physical store.
     *
     * This migration reverses that split:
     *   1. Collapses duplicate SKUs that exist across accounts (keeping the
     *      row with the richest history so no sales/stock-in record is lost).
     *   2. Drops the composite (user_id, sku) unique index.
     *   3. Restores a single GLOBAL UNIQUE index on `sku`.
     *
     * `products.user_id` is intentionally KEPT (nullable) as a
     * "created_by" audit trail for the catalogue, but it is no longer used
     * for data isolation — all roles now read and write the same rows.
     */
    public function up(): void
    {
        $this->collapseDuplicateSkus();

        Schema::table('products', function (Blueprint $table) {
            // Remove the per-account uniqueness constraint (no-op if absent).
            $indexes = $this->indexNames('products');

            if (in_array('products_user_id_sku_unique', $indexes, true)) {
                $table->dropUnique(['user_id', 'sku']);
            }

            // One shared catalogue => one product row per SKU, store-wide.
            if (! in_array('products_sku_unique', $indexes, true)) {
                $table->unique('sku');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (in_array('products_sku_unique', $this->indexNames('products'), true)) {
                $table->dropUnique(['sku']);
            }

            $table->unique(['user_id', 'sku']);
        });
    }

    /**
     * Merge duplicate SKU rows into a single canonical product.
     *
     * The keeper is the row with the most activity (sales + stock-ins +
     * adjustments), falling back to the lowest id. Losers that carry history
     * are re-labelled with a disambiguated SKU instead of being deleted, so
     * historical transactions stay traceable. Untouched duplicates are
     * removed outright.
     */
    private function collapseDuplicateSkus(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $duplicateSkus = DB::table('products')
            ->select('sku')
            ->whereNotNull('sku')
            ->groupBy('sku')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('sku');

        foreach ($duplicateSkus as $sku) {
            $rows = DB::table('products')
                ->where('sku', $sku)
                ->orderBy('id')
                ->get();

            if ($rows->count() < 2) {
                continue;
            }

            $ranked = $rows->sortByDesc(fn ($row) => [
                $this->activityCount($row->id),
                -$row->id,
            ])->values();

            $keeper = $ranked->first();

            foreach ($ranked->slice(1) as $duplicate) {
                if ($this->activityCount($duplicate->id) > 0) {
                    DB::table('products')
                        ->where('id', $duplicate->id)
                        ->update(['sku' => $sku . '-DUP-' . $duplicate->id]);

                    continue;
                }

                DB::table('products')->where('id', $duplicate->id)->delete();
            }

            // Park the orphaned owner pointer on the surviving row.
            if ($keeper && ! $keeper->user_id) {
                $fallbackOwner = DB::table('products')
                    ->where('sku', $sku)
                    ->whereNotNull('user_id')
                    ->value('user_id');

                if ($fallbackOwner) {
                    DB::table('products')
                        ->where('id', $keeper->id)
                        ->update(['user_id' => $fallbackOwner]);
                }
            }
        }
    }

    /**
     * How many transactional records reference this product.
     */
    private function activityCount(int $productId): int
    {
        return $this->countRows('sale_items', 'product_id', $productId)
            + $this->countRows('stock_ins', 'product_id', $productId)
            + $this->countRows('stock_adjustments', 'product_id', $productId);
    }

    private function countRows(string $table, string $column, int $productId): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        return DB::table($table)->where($column, $productId)->count();
    }

    /**
     * List the index names currently defined on a table.
     *
     * The production database is PostgreSQL, but the test suite runs on SQLite
     * (see phpunit.xml), and `pg_indexes` does not exist there. Query the
     * driver-specific catalogue so the migration works on both.
     *
     * @return list<string>
     */
    private function indexNames(string $table): array
    {
        $driver = DB::connection()->getDriverName();

        $rows = match ($driver) {
            'pgsql' => DB::select(
                'select indexname from pg_indexes where tablename = ?',
                [$table]
            ),
            'sqlite' => DB::select(
                'select name as indexname from sqlite_master where type = ? and tbl_name = ?',
                ['index', $table]
            ),
            'mysql' => DB::select(
                'select distinct index_name as indexname from information_schema.statistics where table_schema = database() and table_name = ?',
                [$table]
            ),
            default => [],
        };

        return collect($rows)->pluck('indexname')->all();
    }
};
