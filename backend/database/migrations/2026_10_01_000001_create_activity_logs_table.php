<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A lightweight, human-readable audit feed for the Products page's
     * "Recent Activity" section. Unlike the specialized stock_ins /
     * stock_adjustments / sales tables (which only capture their own flows),
     * this records EVERY meaningful admin action — product add, edit,
     * deactivate/reactivate, stock receive, stock adjust and sale — in a single
     * time-ordered stream so the UI can show "who did what, just now".
     *
     * The user_id is nullable with nullOnDelete so the feed survives an account
     * being deactivated/removed (BRD: accounts are never hard-deleted, but this
     * keeps the audit trail safe regardless).
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            // Machine-readable action key: product_created, product_updated,
            // product_deactivated, product_reactivated, stock_received,
            // stock_adjusted, sale_completed.
            $table->string('action', 40);
            // What the action targeted (product / sale) so a future UI can link
            // back to the subject without parsing the description.
            $table->string('subject_type', 30)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            // Snapshot of the subject's display name at the moment of the action,
            // so the feed stays readable even if the product is later renamed or
            // deactivated.
            $table->string('subject_name', 255)->nullable();
            // Ready-to-render sentence, e.g. "Adjusted Pako: 2 → -1 (Damaged)".
            $table->string('description', 500);
            // Optional structured detail (deltas, before/after, amounts) for
            // richer UI badges without re-querying the source tables.
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['action']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
