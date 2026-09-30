<?php

namespace Tests\Feature;

use App\Models\StockAdjustment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Manual stock adjustment and audit trail.
 *
 * BRD (Inventory Management) — Functional Requirements:
 *   "The system shall allow the user to manually adjust the stock quantity of
 *    an existing item (e.g., logging new deliveries or deducting damaged
 *    goods)."
 *
 * REVISED: the previous version scoped adjustments to products the admin
 * "owned" and hardcoded the same SKU in several tests. The catalogue is SHARED
 * store-wide (so SKU is globally unique and ownership is irrelevant), and
 * access is governed by ROLE.
 */
class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithStore;

    // -------------------------------------------------------------------------
    // Authorization
    // -------------------------------------------------------------------------

    #[Test]
    public function guest_cannot_adjust_stock(): void
    {
        $this->postJson('/api/inventory/adjust', [
            'product_id' => 1,
            'reason'     => 'damaged',
            'delta'      => -2,
        ])->assertUnauthorized();
    }

    #[Test]
    public function cashier_cannot_adjust_stock(): void
    {
        $this->actingAs($this->makeCashier())
            ->postJson('/api/inventory/adjust', [
                'product_id' => $this->makeProduct()->id,
                'reason'     => 'damaged',
                'delta'      => -2,
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Administrator access required.');
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    #[Test]
    public function adjust_requires_product_id(): void
    {
        $this->actingAs($this->makeAdmin())
            ->postJson('/api/inventory/adjust', ['reason' => 'damaged', 'delta' => -2])
            ->assertStatus(422)
            ->assertJsonValidationErrors('product_id');
    }

    #[Test]
    public function adjust_requires_reason(): void
    {
        $this->actingAs($this->makeAdmin())
            ->postJson('/api/inventory/adjust', [
                'product_id' => $this->makeProduct()->id,
                'delta'      => -2,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    #[Test]
    public function adjust_rejects_invalid_reason(): void
    {
        $this->actingAs($this->makeAdmin())
            ->postJson('/api/inventory/adjust', [
                'product_id' => $this->makeProduct()->id,
                'reason'     => 'invalid_reason',
                'delta'      => -2,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    #[Test]
    public function adjust_rejects_zero_delta(): void
    {
        $this->actingAs($this->makeAdmin())
            ->postJson('/api/inventory/adjust', [
                'product_id' => $this->makeProduct()->id,
                'reason'     => 'damaged',
                'delta'      => 0,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('delta');
    }

    #[Test]
    public function adjust_returns_404_for_a_nonexistent_product(): void
    {
        $this->actingAs($this->makeAdmin())
            ->postJson('/api/inventory/adjust', [
                'product_id' => 9999,
                'reason'     => 'damaged',
                'delta'      => -2,
            ])
            ->assertNotFound();
    }

    #[Test]
    public function adjust_rejects_insufficient_stock(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['current_stock' => 2]);

        $this->actingAs($admin)
            ->postJson('/api/inventory/adjust', [
                'product_id' => $product->id,
                'reason'     => 'damaged',
                'delta'      => -5,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('delta');

        $this->assertSame(2, (int) $product->fresh()->current_stock);
        $this->assertDatabaseCount('stock_adjustments', 0);
    }

    // -------------------------------------------------------------------------
    // Successful adjustments
    // -------------------------------------------------------------------------

    #[Test]
    public function admin_can_reduce_stock_with_reason(): void
    {
        $admin = $this->makeAdmin(['name' => 'Store Admin']);
        $product = $this->makeProduct(['current_stock' => 50]);

        $this->actingAs($admin)
            ->postJson('/api/inventory/adjust', [
                'product_id'  => $product->id,
                'reason'      => 'damaged',
                'reason_note' => '2 units damaged during inspection',
                'delta'       => -2,
            ])
            ->assertOk()
            ->assertJsonPath('product.current_stock', 48)
            ->assertJsonPath('adjustment.reason', 'damaged')
            ->assertJsonPath('adjustment.delta', -2)
            ->assertJsonPath('adjustment.stock_before', 50)
            ->assertJsonPath('adjustment.stock_after', 48);

        $this->assertDatabaseHas('stock_adjustments', [
            'product_id'  => $product->id,
            'user_id'     => $admin->id,
            'reason'      => 'damaged',
            'reason_note' => '2 units damaged during inspection',
            'delta'       => -2,
            'stock_before'=> 50,
            'stock_after' => 48,
        ]);
    }

    #[Test]
    public function admin_can_increase_stock_with_positive_delta(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['current_stock' => 50]);

        $this->actingAs($admin)
            ->postJson('/api/inventory/adjust', [
                'product_id' => $product->id,
                'reason'     => 'correction',
                'delta'      => 5,
            ])
            ->assertOk()
            ->assertJsonPath('product.current_stock', 55);

        $this->assertDatabaseHas('stock_adjustments', [
            'product_id'  => $product->id,
            'reason'      => 'correction',
            'delta'       => 5,
            'stock_before'=> 50,
            'stock_after' => 55,
        ]);
    }

    /**
     * The adjustment must be attributed to the responsible admin.
     */
    #[Test]
    public function adjustment_is_logged_with_the_responsible_admin(): void
    {
        $admin = $this->makeAdmin(['name' => 'Store Admin']);
        $product = $this->makeProduct(['current_stock' => 10]);

        $this->actingAs($admin)->postJson('/api/inventory/adjust', [
            'product_id' => $product->id,
            'reason'     => 'internal_transfer',
            'delta'      => -3,
        ])->assertOk();

        $adjustment = StockAdjustment::where('product_id', $product->id)->firstOrFail();

        $this->assertSame($admin->id, $adjustment->user_id);
        $this->assertSame('Store Admin', $adjustment->admin->name);
        $this->assertNotNull($adjustment->created_at);
    }

    #[Test]
    public function multiple_adjustments_are_all_logged_in_order(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['current_stock' => 50]);

        $this->actingAs($admin)->postJson('/api/inventory/adjust', [
            'product_id' => $product->id, 'reason' => 'damaged', 'delta' => -2,
        ])->assertOk();

        $this->actingAs($admin)->postJson('/api/inventory/adjust', [
            'product_id' => $product->id, 'reason' => 'internal_transfer', 'delta' => -3,
        ])->assertOk();

        $this->assertSame(45, (int) $product->fresh()->current_stock);
        $this->assertDatabaseCount('stock_adjustments', 2);

        $adjustments = StockAdjustment::where('product_id', $product->id)->orderBy('id')->get();

        $this->assertSame('damaged', $adjustments[0]->reason);
        $this->assertSame(50, $adjustments[0]->stock_before);
        $this->assertSame(48, $adjustments[0]->stock_after);

        $this->assertSame('internal_transfer', $adjustments[1]->reason);
        $this->assertSame(48, $adjustments[1]->stock_before);
        $this->assertSame(45, $adjustments[1]->stock_after);
    }

    #[Test]
    public function stock_before_plus_delta_equals_stock_after(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['current_stock' => 100]);

        $this->actingAs($admin)->postJson('/api/inventory/adjust', [
            'product_id' => $product->id, 'reason' => 'lost', 'delta' => -10,
        ])->assertOk();

        $a = StockAdjustment::where('product_id', $product->id)->firstOrFail();

        $this->assertSame(100, $a->stock_before);
        $this->assertSame(90, $a->stock_after);
        $this->assertSame($a->stock_after, $a->stock_before + $a->delta);
    }

    /**
     * The catalogue is shared, so an adjustment applies to the item itself and
     * is immediately visible through the master list.
     */
    #[Test]
    public function successful_adjustment_refreshes_the_product_via_api(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['current_stock' => 50]);

        $this->actingAs($admin)->postJson('/api/inventory/adjust', [
            'product_id' => $product->id, 'reason' => 'damaged', 'delta' => -2,
        ])->assertOk();

        $this->actingAs($admin)->getJson('/api/inventory/products')
            ->assertOk()
            ->assertJsonPath('0.id', $product->id)
            ->assertJsonPath('0.current_stock', 48);
    }
}
