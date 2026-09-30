<?php

namespace Tests\Feature;

use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Supplier records.
 *
 * NOTE: Suppliers are listed as Out of Scope in the Detailed BRDs, but the
 * feature exists in the codebase and is Admin-only under the BRD role model
 * ("Admin Role = POS + Inventory + Demand Suggestions + User Management";
 * supplier access sits with Inventory). These tests therefore keep an Admin
 * subject and verify the role guard.
 */
class SupplierTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithStore;

    #[Test]
    public function supplier_table_exists_in_database(): void
    {
        $this->assertTrue(Schema::hasTable('suppliers'), 'The suppliers table should exist.');

        foreach (['id', 'name', 'contact_person', 'phone', 'email', 'is_active', 'created_at', 'updated_at'] as $column) {
            $this->assertContains($column, Schema::getColumnListing('suppliers'), "Missing column: {$column}");
        }
    }

    #[Test]
    public function guest_cannot_access_supplier_endpoints(): void
    {
        $this->getJson('/api/suppliers/active')->assertUnauthorized();
        $this->postJson('/api/suppliers', [])->assertUnauthorized();
        $this->get('/suppliers')->assertRedirect('/login');
    }

    #[Test]
    public function cashier_cannot_access_supplier_endpoints(): void
    {
        $cashier = $this->makeCashier();

        $this->actingAs($cashier)->getJson('/api/suppliers/active')->assertForbidden();
        $this->actingAs($cashier)->postJson('/api/suppliers', ['name' => 'Nope'])->assertForbidden();
        $this->actingAs($cashier)->get('/suppliers')->assertRedirect(route('dashboard'));
    }

    #[Test]
    public function admin_can_open_the_suppliers_page(): void
    {
        $this->actingAs($this->makeAdmin())->get('/suppliers')->assertOk();
    }

    #[Test]
    public function admin_can_add_a_supplier(): void
    {
        $this->actingAs($this->makeAdmin())
            ->postJson('/api/suppliers', [
                'name'           => 'Metro Hardware Supply',
                'contact_person' => 'Ana Reyes',
                'phone'          => '09171234567',
                'email'          => 'ana@metrohardware.test',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('suppliers', ['name' => 'Metro Hardware Supply']);
    }

    #[Test]
    public function only_active_suppliers_are_returned(): void
    {
        Supplier::factory()->create(['name' => 'Active Co', 'is_active' => true]);
        Supplier::factory()->create(['name' => 'Retired Co', 'is_active' => false]);

        $names = collect($this->actingAs($this->makeAdmin())
            ->getJson('/api/suppliers/active')
            ->assertOk()
            ->json())->pluck('name')->all();

        $this->assertContains('Active Co', $names);
        $this->assertNotContains('Retired Co', $names);
    }

    /**
     * A supplier is deactivated, not deleted, so historical stock-ins keep
     * pointing at a real supplier.
     */
    #[Test]
    public function admin_can_deactivate_rather_than_delete_a_supplier(): void
    {
        $admin = $this->makeAdmin();
        $supplier = Supplier::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->deleteJson("/api/suppliers/{$supplier->id}")
            ->assertOk();

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'is_active' => false]);
    }
}
