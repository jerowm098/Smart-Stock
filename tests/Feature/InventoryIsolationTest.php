<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Inventory visibility and the shared store catalogue.
 *
 * BRD (Account Management) — Business rules:
 *   "Staff Role = POS Access Only."
 *   "Admin Role = POS + Inventory + Demand Suggestions + User Management."
 * BRD (Inventory Management) — Security:
 *   "The system shall restrict all access to this module; it must be
 *    completely inaccessible to accounts with the Staff role."
 *   "Direct URL navigation to the inventory dashboard by an unauthenticated or
 *    Staff-level user shall result in an immediate redirect to the login or POS
 *    screen."
 *
 * REVISED (was: InventoryIsolationTest).
 * The previous version asserted *per-account* product isolation. That design
 * was abandoned: the product catalogue is SHARED store-wide, and
 * `products.user_id` is an audit-trail column only — never a data-isolation
 * boundary (see App\Models\Product and the note in AuthController). Access is
 * now governed strictly by ROLE. The "other user's product" cases therefore
 * assert that a Staff account is refused, and that all admins share one
 * catalogue.
 */
class InventoryIsolationTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithStore;

    #[Test]
    public function admin_sees_the_whole_shared_catalogue(): void
    {
        $admin = $this->makeAdmin();

        $this->makeProduct(['sku' => 'SHARED-1', 'name' => 'Item One']);
        $this->makeProduct(['sku' => 'SHARED-2', 'name' => 'Item Two']);

        $this->actingAs($admin)
            ->getJson('/api/inventory/products')
            ->assertOk()
            ->assertJsonCount(2);
    }

    #[Test]
    public function catalogue_is_shared_between_admins_not_scoped_per_account(): void
    {
        $adminA = $this->makeAdmin();
        $adminB = $this->makeAdmin();

        // Created while signed in as A, but visible to B: one store, one catalogue.
        $this->actingAs($adminA)->postJson('/api/inventory/add', [
            'name'              => 'Store Wide Item',
            'sku'               => 'STORE-WIDE-1',
            'price'             => 25,
            'current_stock'     => 10,
            'reorder_threshold' => 2,
        ])->assertCreated();

        $rows = $this->actingAs($adminB)
            ->getJson('/api/inventory/products')
            ->assertOk()
            ->json();

        $this->assertCount(1, $rows);
        $this->assertSame('STORE-WIDE-1', $rows[0]['sku']);
    }

    /**
     * The old test asserted a Staff user could not delete "another user's"
     * product (404). Under the shared-catalogue model ownership is irrelevant —
     * the refusal is purely about ROLE, so the answer is 403.
     */
    #[Test]
    public function staff_cannot_deactivate_a_product_regardless_of_ownership(): void
    {
        $cashier = $this->makeCashier();
        $product = $this->makeProduct(['user_id' => $this->makeAdmin()->id]);

        $this->actingAs($cashier)
            ->deleteJson("/api/inventory/{$product->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => true]);
    }

    /**
     * BRD: "Editing existing product details or deactivating discontinued items."
     * Deactivation preserves the row so the audit trail survives.
     */
    #[Test]
    public function admin_can_deactivate_a_product_and_bring_it_back(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();

        $this->actingAs($admin)->deleteJson("/api/inventory/{$product->id}")->assertOk();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);

        $this->actingAs($admin)->deleteJson("/api/inventory/{$product->id}?reactivate=1")->assertOk();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => true]);
    }

    #[Test]
    public function alerts_are_admin_only(): void
    {
        $admin = $this->makeAdmin();
        $cashier = $this->makeCashier();

        $this->makeProduct(['sku' => 'LOW-1', 'current_stock' => 1, 'reorder_threshold' => 5]);
        $this->makeProduct(['sku' => 'OK-1', 'current_stock' => 80, 'reorder_threshold' => 5]);

        $skus = collect($this->actingAs($admin)->getJson('/api/inventory/alerts')->assertOk()->json())
            ->pluck('sku')->all();

        $this->assertContains('LOW-1', $skus);
        $this->assertNotContains('OK-1', $skus);

        $this->actingAs($cashier)->getJson('/api/inventory/alerts')->assertForbidden();
    }

    #[Test]
    public function stored_product_records_the_creating_admin(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->postJson('/api/inventory/add', [
            'name'              => 'Test Widget',
            'sku'               => 'TW-UPD-001',
            'category'          => 'Widgets',
            'price'             => 99.50,
            'current_stock'     => 10,
            'reorder_threshold' => 3,
        ])->assertCreated();

        $this->assertDatabaseHas('products', ['sku' => 'TW-UPD-001', 'user_id' => $admin->id]);
    }

    #[Test]
    public function staff_cannot_add_products(): void
    {
        $cashier = $this->makeCashier();

        $this->actingAs($cashier)->postJson('/api/inventory/add', [
            'name'              => 'Nope',
            'sku'               => 'NP-001',
            'price'             => 1,
            'current_stock'     => 1,
            'reorder_threshold' => 1,
        ])->assertForbidden();

        $this->assertDatabaseMissing('products', ['sku' => 'NP-001']);
    }

    #[Test]
    public function guest_cannot_access_inventory_api(): void
    {
        $this->getJson('/api/inventory/products')->assertUnauthorized();
        $this->getJson('/api/inventory/alerts')->assertUnauthorized();

        $this->postJson('/api/inventory/add', [
            'name'              => 'Nope',
            'sku'               => 'NP-GUEST',
            'price'             => 1,
            'current_stock'     => 1,
            'reorder_threshold' => 1,
        ])->assertUnauthorized();
    }
}
