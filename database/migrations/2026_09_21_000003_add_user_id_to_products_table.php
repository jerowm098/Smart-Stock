<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SS-70: Associates every product with the user who created it so that
     * each account only ever sees its own inventory.
     *
     * Legacy rows (created before this column existed) are assigned to the
     * existing `jerome01` account so its data is preserved, while newly
     * created accounts start with an empty inventory.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->cascadeOnDelete();
            }
        });

        $ownerId = DB::table('users')->where('username', 'jerome01')->value('id');

        if ($ownerId === null) {
            $ownerId = DB::table('users')->orderBy('id')->value('id');
        }

        if ($ownerId !== null) {
            DB::table('products')
                ->whereNull('user_id')
                ->update(['user_id' => $ownerId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};