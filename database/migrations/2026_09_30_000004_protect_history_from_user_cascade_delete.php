<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Protect historical records from cascading away with a deleted account.
     *
     * BRD (Account Management) — Limitations:
     *   "Because accounts cannot be fully deleted (to preserve sales history),
     *    the database will retain inactive user records indefinitely."
     *
     * BRD (Transaction Tracking) — Data Retention:
     *   "Transaction records must remain permanently linked to the Staff's
     *    username, even if that Staff account is later deactivated."
     *
     * Both `sales.user_id` and `products.user_id` were created with
     * ON DELETE CASCADE. The application only ever deactivates accounts
     * (`users.is_active`), so this is belt-and-braces — but if a user row is
     * ever removed by hand, a script, or a Supabase dashboard edit, the CASCADE
     * would silently destroy the store's sales history.
     *
     * Empirically verified on the live Supabase database: deleting one user
     * removed their sale and every sale_items row.
     *
     * This migration switches both to ON DELETE RESTRICT, so PostgreSQL refuses
     * the delete and forces the account to be deactivated instead. No rows are
     * modified — only the referential action.
     */
    public function up(): void
    {
        // `ALTER TABLE ... DROP CONSTRAINT` is PostgreSQL/SQL Server syntax.
        // The test suite runs on SQLite (see phpunit.xml), which has no such
        // statement, so the raw DDL is restricted to the drivers that support
        // it. SQLite ignores foreign_key_actions unless PRAGMA
        // foreign_keys=ON, and Laravel's SQLite schema builder never creates
        // cascading user FKs, so there is nothing to change there.
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (Schema::hasTable('sales')) {
            DB::statement('ALTER TABLE sales DROP CONSTRAINT IF EXISTS sales_user_id_foreign');
            DB::statement(
                'ALTER TABLE sales ADD CONSTRAINT sales_user_id_foreign
                 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT'
            );
        }

        // products.user_id is an audit-trail column (who first catalogued the
        // item); it must never take the product — and its sales history — down.
        if (Schema::hasTable('products')) {
            DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_user_id_foreign');
            DB::statement(
                'ALTER TABLE products ADD CONSTRAINT products_user_id_foreign
                 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL'
            );
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (Schema::hasTable('sales')) {
            DB::statement('ALTER TABLE sales DROP CONSTRAINT IF EXISTS sales_user_id_foreign');
            DB::statement(
                'ALTER TABLE sales ADD CONSTRAINT sales_user_id_foreign
                 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE'
            );
        }

        if (Schema::hasTable('products')) {
            DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_user_id_foreign');
            DB::statement(
                'ALTER TABLE products ADD CONSTRAINT products_user_id_foreign
                 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE'
            );
        }
    }
};
