<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * SS-92 / SS-93 / SS-95: Tests for supplier records.
 *
 * Covers:
 *  - Supplier table schema is present
 *  - GET active suppliers requires authentication and admin role
 *  - POST supplier requires authentication and admin role
 *  - Validation errors for invalid data
 *  - Active suppliers are returned; inactive are hidden
 *  - SS-95: Adding a supplier profile verifies the persisted database entry
 */
class SupplierTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function supplier_table_exists_in_database(): void
    {
        $this->assertTrue(
            Schema::hasTable('suppliers'),
            'The suppliers table should exist.'
        );

        $columns = Schema::getColumnListing('suppliers');

        $requiredColumns = ['id', 'name', 'contact_person', 'phone', 'email', 'is_active', 'created_at', 'updated_at'];
        foreach ($requiredColumns as $column) {
            $this->assertContains($column, $columns, "The suppliers table is missing the {$column} column.");
        }
    }

    #[Test]
    public function active_suppliers_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/suppliers/active')->assertStatus(401);
    }

    #[Test]
    public function active_suppliers_endpoint_requires_admin_role(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($user)->getJson('/api/suppliers/active')->assertStatus(403);
    }

    #[Test]
    public function active_suppliers_endpoint_returns_empty_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->getJson('/api/suppliers/active')
            ->assertStatus(200)
            ->assertJson([]);
    }

    #[Test]
    public function active_suppliers_endpoint_returns_active_suppliers_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Supplier::factory()->create(['name' => 'Active Supplier', 'is_active' => true]);
        Supplier::factory()->create(['name' => 'Inactive Supplier', 'is_active' => false]);

        $response = $this->actingAs($admin)->getJson('/api/suppliers/active');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Active Supplier');
    }

    #[Test]
    public function store_supplier_requires_authentication(): void
    {
        $this->postJson('/api/suppliers', [
            'name' => 'Test Supplier',
        ])->assertStatus(401);
    }

    #[Test]
    public function store_supplier_requires_admin_role(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($user)->postJson('/api/suppliers', [
            'name' => 'Test Supplier',
        ])->assertStatus(403);
    }

    #[Test]
    public function store_supplier_validates_required_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->postJson('/api/suppliers', [
            'name' => '',
            'contact_person' => 'Person',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    #[Test]
    public function admin_can_create_supplier(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/suppliers', [
            'name' => 'Hardware World',
            'contact_person' => 'Juan Dela Cruz',
            'phone' => '09123456789',
            'email' => 'contact@hardwareworld.com',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Supplier added successfully')
            ->assertJsonPath('supplier.name', 'Hardware World');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Hardware World',
            'contact_person' => 'Juan Dela Cruz',
            'phone' => '09123456789',
            'email' => 'contact@hardwareworld.com',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function store_supplier_ignores_is_active_input(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->postJson('/api/suppliers', [
            'name' => 'Test Co',
            'is_active' => false,
        ])->assertStatus(201);

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Test Co',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function ss95_admin_can_add_supplier_profile_and_verify_database_entry(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/suppliers', [
            'name' => 'TechParts Inc',
            'contact_person' => 'Maria Santos',
            'phone' => '09876543210',
            'email' => 'maria@techparts.com',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Supplier added successfully')
            ->assertJsonPath('supplier.name', 'TechParts Inc')
            ->assertJsonPath('supplier.contact_person', 'Maria Santos')
            ->assertJsonPath('supplier.phone', '09876543210')
            ->assertJsonPath('supplier.email', 'maria@techparts.com');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'TechParts Inc',
            'contact_person' => 'Maria Santos',
            'phone' => '09876543210',
            'email' => 'maria@techparts.com',
            'is_active' => true,
        ]);
    }
}
