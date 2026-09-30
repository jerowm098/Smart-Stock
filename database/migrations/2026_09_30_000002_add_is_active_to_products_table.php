<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BRD (Inventory Management): "Editing existing product details or
     * deactivating discontinued items."
     *
     * Deactivation (not deletion) keeps historical SaleItem / StockIn /
     * StockAdjustment audit rows intact and removes the item from the POS
     * catalog and the inventory master list.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('reorder_threshold');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
