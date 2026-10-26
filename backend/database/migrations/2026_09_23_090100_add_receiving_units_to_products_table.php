<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SS-40 / SS-89: Add receiving-unit metadata to products so stock-in
     * transactions can convert bulk packaging into individual pieces.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'receiving_unit')) {
                $table->string('receiving_unit', 20)->default('piece')->after('current_stock');
            }

            if (! Schema::hasColumn('products', 'pieces_per_receiving_unit')) {
                $table->integer('pieces_per_receiving_unit')->default(1)->after('receiving_unit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'pieces_per_receiving_unit')) {
                $table->dropColumn('pieces_per_receiving_unit');
            }

            if (Schema::hasColumn('products', 'receiving_unit')) {
                $table->dropColumn('receiving_unit');
            }
        });
    }
};