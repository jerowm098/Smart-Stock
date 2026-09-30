<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BRD (Demand Forecasting & Order Suggestions):
     *   Scope: "Ability for the Admin to dismiss a suggestion or mark it as 'Ordered'."
     *   State model: "Suggestion Record Lifecycle"
     *   Business rules: suggestions are flagged/dismissed/ordered states.
     *
     * The nightly forecasting job (forecast:orders) upserts one active row per
     * product. Rows already marked `ordered` or `dismissed` are preserved so the
     * Admin's decisions survive re-computation, matching the
     * "Suggestion Record Lifecycle" state model.
     */
    public function up(): void
    {
        if (Schema::hasTable('order_suggestions')) {
            return;
        }

        Schema::create('order_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // active = still shown on the dashboard; ordered/dismissed = actioned.
            $table->string('status', 20)->default('active');

            // Snapshot of the BRD formulas at the time the suggestion was made.
            $table->decimal('avg_daily', 10, 2)->default(0);
            $table->decimal('reorder_point', 10, 2)->default(0);
            $table->decimal('suggested_qty', 10, 2)->default(0);
            $table->integer('current_stock')->default(0);
            $table->integer('window_days')->default(30);
            $table->string('urgency', 20)->default('watch');
            $table->text('reason')->nullable();

            $table->timestamp('generated_at')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->foreignId('actioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'urgency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_suggestions');
    }
};
