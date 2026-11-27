<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('category')
                ->constrained('categories')
                ->nullOnDelete();
        });

        // Backfill distinct existing product categories into the categories table
        // and link products.category_id while keeping products.category intact.
        $existing = DB::table('products')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        $now = now();
        foreach ($existing as $rawName) {
            $name = trim((string) $rawName);
            if ($name === '') {
                continue;
            }

            $categoryId = DB::table('categories')->where('name', $name)->value('id');
            if (! $categoryId) {
                $categoryId = DB::table('categories')->insertGetId([
                    'name' => $name,
                    'description' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('products')
                ->where('category', $rawName)
                ->update(['category_id' => $categoryId]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('categories');
    }
};
