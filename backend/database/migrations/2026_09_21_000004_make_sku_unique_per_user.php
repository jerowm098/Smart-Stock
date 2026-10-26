<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SS-49: The original products migration applied a global UNIQUE
     * constraint on `sku`, but the application validates SKU uniqueness
     * per user (see InventoryController::store). This migration replaces
     * the global constraint with a composite (user_id, sku) unique so
     * that every account can carry the same sample SKUs without
     * colliding.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['sku']);
            $table->unique(['user_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'sku']);
            $table->unique(['sku']);
        });
    }
};
