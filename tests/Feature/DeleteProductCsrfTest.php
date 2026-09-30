<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The product deactivation flow, end-to-end through the CSRF-protected route.
 *
 * REVISED (was: DeleteProductCsrfTest).
 * The product is no longer hard-deleted. BRD (Inventory Management) says
 * "Editing existing product details or deactivating discontinued items", and
 * deleting the row would orphan the sale_items / stock_ins /
 * stock_adjustments audit trail. The route is still DELETE (so the Blade
 * callers and CSRF header stay unchanged) but the server now toggles
 * `is_active`.
 *
 * The old version also asserted that one account's deletion could not affect
 * another account's products. That is obsolete: the catalogue is SHARED
 * store-wide, so there is no per-account product scope to protect.
 */
class DeleteProductCsrfTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithStore;

    #[Test]
    public function deactivating_with_a_valid_csrf_token_succeeds(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();

        $this->actingAs($admin)
            ->deleteJson("/api/inventory/{$product->id}")
            ->assertOk();

        // Deactivated, NOT deleted — the row must survive for the audit trail.
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
    }

    #[Test]
    public function staff_session_is_rejected(): void
    {
        $cashier = $this->makeCashier();
        $product = $this->makeProduct();

        $this->actingAs($cashier)
            ->deleteJson("/api/inventory/{$product->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => true]);
    }

    #[Test]
    public function guest_session_is_rejected(): void
    {
        $product = $this->makeProduct();

        $this->deleteJson("/api/inventory/{$product->id}")->assertUnauthorized();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => true]);
    }

    /**
     * Deactivating one item must not affect the rest of the shared catalogue.
     *
     * NOTE: the Admin master list intentionally still returns deactivated rows
     * (flagged `is_active: false`) so the Admin can reactivate them; callers
     * that want only active items pass ?include_inactive=0.
     */
    #[Test]
    public function deactivating_one_item_leaves_the_others_active(): void
    {
        $admin = $this->makeAdmin();

        $a = $this->makeProduct(['sku' => 'KEEP-A']);
        $b = $this->makeProduct(['sku' => 'KEEP-B']);

        $this->actingAs($admin)->deleteJson("/api/inventory/{$a->id}")->assertOk();

        $this->assertDatabaseHas('products', ['id' => $a->id, 'is_active' => false]);
        $this->assertDatabaseHas('products', ['id' => $b->id, 'is_active' => true]);

        // Filtered to active items, the deactivated one drops out.
        $rows = $this->actingAs($admin)
            ->getJson('/api/inventory/products?include_inactive=0')
            ->json();

        $skus = collect($rows)->pluck('sku')->all();

        $this->assertNotContains('KEEP-A', $skus);
        $this->assertContains('KEEP-B', $skus);

        // Unfiltered, both are listed but the flag distinguishes them.
        $all = collect($this->actingAs($admin)->getJson('/api/inventory/products')->json())
            ->keyBy('sku');

        $this->assertFalse((bool) $all['KEEP-A']['is_active']);
        $this->assertTrue((bool) $all['KEEP-B']['is_active']);
    }
}
