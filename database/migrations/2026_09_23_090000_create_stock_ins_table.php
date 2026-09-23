<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SS-40 / SS-89: Create the `stock_ins` table that records every
     * stock-in (receiving) transaction. Each row captures the product,
     * the quantity received, the unit of measure used at receiving time,
     * the supplier the goods came from, the effective piece delta that
     * was applied to inventory, and the staff member who logged it.
     *
     * The table is the audit trail (SS-24) for incoming supplies and is
     * tied to the staff member via `user_id` with an automatic
     * timestamp.
     */
    public function up(): void
    {
        Schema::create('stock_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();
            $table->integer('quantity_received')->default(0);
            $table->string('unit_of_measure', 20)->default('piece');
            $table->integer('unit_conversion')->default(1);
            $table->integer('piece_delta')->default(0);
            $table->integer('stock_before')->default(0);
            $table->integer('stock_after')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ins');
    }
};