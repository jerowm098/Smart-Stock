<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * SS-38 / SS-99: Tests for manual stock adjustment and audit trail verification.
 *
 * Covers:
 *  - SS-99: Admin can reduce stock via POST /api/inventory/adjust with a reason
 *  - SS-99: Stock delta correctly updates the current_stock column
 *  - SS-99: Adjustment is logged in the audit trail with reason and responsible admin
 *  - SS-99: Adjustment entries include timestamp, stock_before, stock_after, delta, reason
 *  - Validation and error cases (insufficient stock, invalid reason, missing fields)
 */
class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(User $user, array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'user_id' => $user->id,
            'name' => 'Wireless Mouse',
            'sku' => 'WM-001',
            'category' => 'Electronics',
            'price' => 29.99,
            'current_stock' => 50,
            'reorder_threshold' => 10,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // SS-99: Successful stock reduction
    // -------------------------------------------------------------------------

    #[Test]
    public function guest_cannot_adjust_stock(): void
    {
        $this->postJson('/api/inventory/adjust', [
            'product_id' => 1,
            'reason' => 'damaged',
            'delta' => -2,
        ])->assertStatus(401);
    }

    #[Test]
    public function products_page_shows_adjust_action_only_for_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->createProduct($admin);
        $this->createProduct($cashier);

        $adminResponse = $this->actingAs($admin)->get('/products');
        $cashierResponse = $this->actingAs($cashier)->get('/products');

        // The product table is rendered client-side via JS, so we verify the
        // admin-only flag and the conditional template string in the source.
        $adminResponse->assertStatus(200)
            ->assertSee('const canAdjustStock = true;', false)
            ->assertSee("canAdjustStock ? '<button class=\"btn-adjust\"", false)
            ->assertSee('function openAdjustModal(', false);

        $cashierResponse->assertStatus(200)
            ->assertSee('const canAdjustStock = false;', false)
            ->assertSee("canAdjustStock ? '<button class=\"btn-adjust\"", false)
            ->assertSee('function openAdjustModal(', false);
    }

    #[Test]
    public function cashier_cannot_adjust_stock(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)
            ->postJson('/api/inventory/adjust', [
                'product_id' => 1,
                'reason' => 'damaged',
                'delta' => -2,
            ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Administrator access required.');
    }

    #[Test]
    public function adjust_requires_product_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson('/api/inventory/adjust', [
                'reason' => 'damaged',
                'delta' => -2,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('product_id');
    }

    #[Test]
    public function adjust_requires_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct($admin);

        $this->actingAs($admin)
            ->postJson('/api/inventory/adjust', [
                'product_id' => $product->id,
                'delta' => -2,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    #[Test]
    public function adjust_rejects_invalid_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct($admin);

        $this->actingAs($admin)
            ->postJson('/api/inventory/adjust', [
                'product_id' => $product->id,
                'reason' => 'invalid_reason',
                'delta' => -2,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    #[Test]
    public function adjust_rejects_zero_delta(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct($admin);

        $this->actingAs($admin)
            ->postJson('/api/inventory/adjust', [
                'product_id' => $product->id,
                'reason' => 'damaged',
                'delta' => 0,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('delta');
    }

    #[Test]
    public function adjust_returns_404_for_nonexistent_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson('/api/inventory/adjust', [
                'product_id' => 9999,
                'reason' => 'damaged',
                'delta' => -2,
            ])
            ->assertStatus(404)
            ->assertJsonPath('message', 'Product not found or access denied.');
    }

    #[Test]
    public function adjust_returns_404_for_other_users_product(): void
    {
        $ownerA = User::factory()->create(['role' => 'admin']);
        $ownerB = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct($ownerA);

        $this->actingAs($ownerB)
            ->postJson('/api/inventory/adjust', [
                'product_id' => $product->id,
                'reason' => 'damaged',
                'delta' => -2,
            ])
            ->assertStatus(404);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'user_id' => $ownerA->id,
            'current_stock' => 50,
        ]);
    }

    #[Test]
    public function admin_can_reduce_stock_with_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct($admin, ['current_stock' => 50]);

        $response = $this->actingAs($admin)->postJson('/api/inventory/adjust', [
            'product_id' => $product->id,
            'reason' => 'damaged',
            'reason_note' => '2 units damaged during inspection',
            'delta' => -2,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Stock adjusted successfully')
            ->assertJsonPath('product.current_stock', 48)
            ->assertJsonPath('adjustment.reason', 'damaged')
            ->assertJsonPath('adjustment.delta', -2)
            ->assertJsonPath('adjustment.stock_before', 50)
            ->assertJsonPath('adjustment.stock_after', 48);

        // Product stock was updated
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'current_stock' => 48,
        ]);

        // Adjustment was logged in audit trail (stock_adjustments table)
        $this->assertDatabaseHas('stock_adjustments', [
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'reason' => 'damaged',
            'reason_note' => '2 units damaged during inspection',
            'delta' => -2,
            'stock_before' => 50,
            'stock_after' => 48,
        ]);
    }

    #[Test]
    public function admin_can_increase_stock_with_positive_delta(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct($admin, ['current_stock' => 50]);

        $response = $this->actingAs($admin)->postJson('/api/inventory/adjust', [
            'product_id' => $product->id,
            'reason' => 'correction',
            'delta' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('product.current_stock', 55)
            ->assertJsonPath('adjustment.stock_before', 50)
            ->assertJsonPath('adjustment.stock_after', 55);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'current_stock' => 55,
        ]);

        $this->assertDatabaseHas('stock_adjustments', [
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'reason' => 'correction',
            'delta' => 5,
            'stock_before' => 50,
            'stock_after' => 55,
        ]);
    }

    #[Test]
    public function adjustment_entry_includes_timestamp(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct($admin, ['current_stock' => 10]);

        $this->actingAs($admin)->postJson('/api/inventory/adjust', [
            'product_id' => $product->id,
            'reason' => 'lost',
            'delta' => -1,
        ])->assertStatus(200);

        $adjustment = StockAdjustment::where('product_id', $product->id)->first();

        $this->assertNotNull($adjustment);
        $this->assertNotNull($adjustment->created_at);
        $this->assertNotNull($adjustment->updated_at);
    }

    #[Test]
    public function adjustment_is_logged_with_responsible_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Store Admin']);
        $product = $this->createProduct($admin, ['current_stock' => 10]);

        $this->actingAs($admin)->postJson('/api/inventory/adjust', [
            'product_id' => $product->id,
            'reason' => 'internal_transfer',
            'delta' => -3,
        ])->assertStatus(200);

        $adjustment = StockAdjustment::where('product_id', $product->id)->first();

        $this->assertNotNull($adjustment);
        $this->assertEquals($admin->id, $adjustment->user_id);
        $this->assertEquals('Store Admin', $adjustment->admin->name);
    }

    #[Test]
    public function adjust_rejects_insufficient_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct($admin, ['current_stock' => 2]);

        $this->actingAs($admin)
            ->postJson('/api/inventory/adjust', [
                'product_id' => $product->id,
                'reason' => 'damaged',
                'delta' => -5,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('delta');

        // Stock should NOT have been modified
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'current_stock' => 2,
        ]);

        // No adjustment should have been logged
        $this->assertDatabaseMissing('stock_adjustments', [
            'product_id' => $product->id,
        ]);
    }

    #[Test]
    public function successful_adjustment_refreshes_product_via_api(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct($admin, ['current_stock' => 50]);

        $this->actingAs($admin)->postJson('/api/inventory/adjust', [
            'product_id' => $product->id,
            'reason' => 'damaged',
            'delta' => -2,
        ])->assertStatus(200);

        $apiResponse = $this->actingAs($admin)->getJson('/api/inventory/products');
        $apiResponse->assertStatus(200)
            ->assertJsonPath('0.id', $product->id)
            ->assertJsonPath('0.current_stock', 48);
    }

    #[Test]
    public function multiple_adjustments_are_all_logged(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct($admin, ['current_stock' => 50]);

        $this->actingAs($admin)->postJson('/api/inventory/adjust', [
            'product_id' => $product->id,
            'reason' => 'damaged',
            'delta' => -2,
        ])->assertStatus(200);

        $this->actingAs($admin)->postJson('/api/inventory/adjust', [
            'product_id' => $product->id,
            'reason' => 'internal_transfer',
            'delta' => -3,
        ])->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'current_stock' => 45,
        ]);

        $this->assertDatabaseCount('stock_adjustments', 2);

        $adjustments = StockAdjustment::where('product_id', $product->id)->orderBy('id')->get();
        $this->assertEquals('damaged', $adjustments[0]->reason);
        $this->assertEquals(50, $adjustments[0]->stock_before);
        $this->assertEquals(48, $adjustments[0]->stock_after);
        $this->assertEquals('internal_transfer', $adjustments[1]->reason);
        $this->assertEquals(48, $adjustments[1]->stock_before);
        $this->assertEquals(45, $adjustments[1]->stock_after);
    }

    #[Test]
    public function adjustment_stock_before_after_are_consistent(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct($admin, ['current_stock' => 100]);

        $this->actingAs($admin)->postJson('/api/inventory/adjust', [
            'product_id' => $product->id,
            'reason' => 'lost',
            'delta' => -10,
        ])->assertStatus(200);

        $adjustment = StockAdjustment::where('product_id', $product->id)->first();

        $this->assertEquals(100, $adjustment->stock_before);
        $this->assertEquals(90, $adjustment->stock_after);
        $this->assertEquals(-10, $adjustment->delta);
        $this->assertEquals(100 - 10, $adjustment->stock_before + $adjustment->delta);
    }
}