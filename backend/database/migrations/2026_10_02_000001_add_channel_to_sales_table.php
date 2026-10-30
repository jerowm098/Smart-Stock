<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distinguish an "Admin transaction" from a "Cashier transaction".
     *
     * A sale already records *who* rang it up (sales.user_id), but the seller's
     * role can change later (e.g. a cashier promoted to admin), which would
     * silently re-label history. Persisting the channel at checkout time keeps
     * the transaction record immutable.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'channel')) {
                $table->string('channel', 20)->default('cashier')->after('change_amount')->index();
            }
        });

        // Backfill history from the role the seller held when the sale was made.
        $adminIds = DB::table('users')->where('role', 'admin')->pluck('id');

        if ($adminIds->isNotEmpty()) {
            DB::table('sales')->whereIn('user_id', $adminIds)->update(['channel' => 'admin']);
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'channel')) {
                $table->dropColumn('channel');
            }
        });
    }
};
