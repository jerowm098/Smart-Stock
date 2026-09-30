<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BRD (Account Management): "The system shall allow an Admin to deactivate
     * a Staff account instead of deleting it."
     *
     * Adding a soft-delete style flag so historical sales stay linked to the
     * employee who handled them (Transaction Tracking — Data Retention).
     *
     * NOTE: idem deactivation uses `is_active` rather than Laravel's
     * `deleted_at` because BRD explicitly says accounts "cannot be fully
     * deleted"; keeping a plain boolean makes the intent obvious and avoids
     * accidentally soft-deleting rows through global scopes.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
