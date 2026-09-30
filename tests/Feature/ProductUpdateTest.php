<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Editing existing product details.
 *
 * BRD (Inventory Management) — "The system shall allow the user to update the
 * selling price of existing items" and "Editing existing product details".
 *
 * REVISED: the previous version created products owned by the acting user with
 * a hardcoded SKU. The catalogue is shared store-wide, so ownership does not
 * gate updates — ROLE does, and SKU is globally unique.
 */
class ProductUpdateTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithStore;

    // -------------------------------------------------------------------------
    // Authorization
    // -------------------------------------------------------------------------

    #[Test]
    public function update_endpoint_returns_401_for_guest(): void
    {
        $this->putJson('/api/inventory/update', [
            'id' => 1, 'name' => 'Updated', 'sku' => 'WM-UPD',
            'price' => 10, 'current_stock' => 5, 'reorder_threshold' => 1,
        ])->assertUnauthorized();
    }

    #[Test]
    public function cashier_cannot_update_products(): void
    {
        $this->actingAs($this->makeCashier())
            ->putJson('/api/inventory/update', [
                'id' => 1, 'name' => 'Updated', 'sku' => 'WM-UPD',
                'price' => 10, 'current_stock' => 5, 'reorder_threshold' => 1,
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Administrator access required.');
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    #[Test]
    public function update_endpoint_requires_id(): void
    {
        $this->actingAs($this->makeAdmin())
            ->putJson('/api/inventory/update', [
                'name' => 'Updated', 'sku' => 'WM-UPD',
                'price' => 10, 'current_stock' => 5, 'reorder_threshold' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('id');
    }

    #[Test]
    public function update_rejects_a_blank_name(): void
    {
        $this->actingAs($this->makeAdmin())
            ->putJson('/api/inventory/update', [
                'id' => $this->makeProduct()->id, 'name' => '',
                'sku' => 'WM-UPD', 'price' => 10,
                'current_stock' => 5, 'reorder_threshold' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    /**
     * BRD Business rules: "Prices must be positive numerical values."
     */
    #[Test]
    public function update_rejects_a_negative_price(): void
    {
        $this->actingAs($this->makeAdmin())
            ->putJson('/api/inventory/update', [
                'id' => $this->makeProduct()->id, 'name' => 'Item',
                'sku' => 'WM-UPD', 'price' => -5,
                'current_stock' => 5, 'reorder_threshold' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('price');
    }

    #[Test]
    public function update_rejects_a_duplicate_sku(): void
    {
        $admin = $this->makeAdmin();
        $existing = $this->makeProduct(['sku' => 'TAKEN-1']);
        $other = $this->makeProduct();

        $this->actingAs($admin)
            ->putJson('/api/inventory/update', [
                'id' => $other->id, 'name' => 'Clashing',
                'sku' => 'TAKEN-1', 'price' => 10,
                'current_stock' => 5, 'reorder_threshold' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('sku');

        $this->assertDatabaseHas('products', ['id' => $existing->id, 'sku' => 'TAKEN-1']);
    }

    // -------------------------------------------------------------------------
    // Successful update
    // -------------------------------------------------------------------------

    #[Test]
    public function admin_can_update_a_product(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct([
            'name'              => 'Wireless Mouse',
            'price'             => 29.99,
            'current_stock'     => 50,
            'reorder_threshold' => 10,
        ]);

        $this->actingAs($admin)
            ->putJson('/api/inventory/update', [
                'id'                 => $product->id,
                'name'               => 'Wireless Mouse Pro',
                'sku'                => $product->sku,
                'price'              => 39.99,
                'current_stock'      => 60,
                'reorder_threshold'  => 12,
            ])
            ->assertOk();

        $this->assertDatabaseHas('products', [
            'id'                => $product->id,
            'name'              => 'Wireless Mouse Pro',
            'price'             => 39.99,
            'current_stock'     => 60,
            'reorder_threshold' => 12,
        ]);
    }

    /**
     * The shared catalogue means one admin's edit is visible to every other
     * admin (there is no per-account copy to keep in sync).
     */
    #[Test]
    public function update_in_one_admin_account_is_visible_to_another(): void
    {
        $adminA = $this->makeAdmin();
        $adminB = $this->makeAdmin();

        $product = $this->makeProduct(['name' => 'Shared Item', 'price' => 10]);

        $this->actingAs($adminA)->putJson('/api/inventory/update', [
            'id' => $product->id, 'name' => 'Renamed By A', 'sku' => $product->sku,
            'price' => 20, 'current_stock' => 5, 'reorder_threshold' => 1,
        ])->assertOk();

        $rows = $this->actingAs($adminB)->getJson('/api/inventory/products')->assertOk()->json();

        $this->assertSame('Renamed By A', collect($rows)->firstWhere('id', $product->id)['name']);
    }

    #[Test]
    public function update_endpoint_requires_a_real_product(): void
    {
        $this->actingAs($this->makeAdmin())
            ->putJson('/api/inventory/update', [
                'id' => 999999, 'name' => 'Ghost', 'sku' => 'GH-1',
                'price' => 10, 'current_stock' => 1, 'reorder_threshold' => 1,
            ])
            ->assertNotFound();
    }
}
