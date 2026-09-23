<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * SS-90: Automated tests for Stock-In / Receiving functionality.
 *
 * Covers:
 *  - SS-87: Stock-In page loads correctly for authenticated users
 *  - SS-88: POST /api/inventory/stock-in endpoint validation and authorization
 *  - SS-89: Stock increment, audit trail creation, ownership scoping, supplier association
 *  - Receiving-unit conversion and atomic transaction handling
 */
class StockInTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function createRegularUser(): User
    {
        return User::factory()->create(['role' => 'cashier']);
    }

    private function createProductForUser(User $user, array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'user_id' => $user->id,
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'category' => 'Test Category',
            'price' => 99.99,
            'current_stock' => 10,
            'reorder_threshold' => 5,
            'receiving_unit' => 'box',
            'pieces_per_receiving_unit' => 12, // 1 box = 12 pieces
        ], $overrides));
    }

    private function createActiveSupplier(): Supplier
    {
        return Supplier::factory()->create(['is_active' => true]);
    }

    #[Test]
    public function guest_cannot_access_stock_in_page(): void
    {
        $response = $this->get('/stock-in');
        $response->assertRedirect('/login');
    }

    #[Test]
    public function authenticated_user_can_access_stock_in_page(): void
    {
        $user = $this->createRegularUser();
        $response = $this->actingAs($user)->get('/stock-in');
        $response->assertStatus(200);
        $response->assertSee('Stock-In / Receiving');
        $response->assertSee('Receive Stock');
    }

    #[Test]
    public function guest_cannot_access_stock_in_api_endpoint(): void
    {
        $response = $this->postJson('/api/inventory/stock-in', []);
        $response->assertStatus(401);
    }

    #[Test]
    public function non_admin_cannot_access_stock_in_api_endpoint(): void
    {
        $user = $this->createRegularUser();
        $response = $this->actingAs($user)->postJson('/api/inventory/stock-in', []);
        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Administrator access required.');
    }

    #[Test]
    public function stock_in_endpoint_validates_required_fields(): void
    {
        $admin = $this->createAdminUser();
        $product = $this->createProductForUser($admin);

        $response = $this->actingAs($admin)->postJson('/api/inventory/stock-in', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['product_id', 'quantity_received', 'unit_of_measure']);
    }

    #[Test]
    public function stock_in_endpoint_validates_product_ownership(): void
    {
        $admin = $this->createAdminUser();
        $otherUser = $this->createRegularUser();
        $product = $this->createProductForUser($otherUser); // Product belongs to other user

        $response = $this->actingAs($admin)->postJson('/api/inventory/stock-in', [
            'product_id' => $product->id,
            'quantity_received' => 5,
            'unit_of_measure' => 'box',
        ]);
        $response->assertStatus(404);
        $response->assertJsonPath('message', 'Product not found or access denied.');
    }

    #[Test]
    public function stock_in_endpoint_validates_unit_of_measure_match(): void
    {
        $admin = $this->createAdminUser();
        $product = $this->createProductForUser($admin, ['receiving_unit' => 'box']);

        $response = $this->actingAs($admin)->postJson('/api/inventory/stock-in', [
            'product_id' => $product->id,
            'quantity_received' => 5,
            'unit_of_measure' => 'piece', // Mismatch: product expects 'box'
        ]);
        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Unit of measure must match the product\'s receiving unit (box)');
    }

    #[Test]
    public function stock_in_endpoint_creates_audit_record_and_increments_stock(): void
    {
        $admin = $this->createAdminUser();
        $product = $this->createProductForUser($admin);
        $supplier = $this->createActiveSupplier();

        $initialStock = $product->current_stock;
        $quantityReceived = 3;
        $expectedDelta = $quantityReceived * $product->pieces_per_receiving_unit; // 3 boxes * 12 pieces/box = 36 pieces

        $response = $this->actingAs($admin)->postJson('/api/inventory/stock-in', [
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'quantity_received' => $quantityReceived,
            'unit_of_measure' => 'box',
            'note' => 'PO #12345',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Stock received successfully');

        // Verify stock was incremented correctly
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'current_stock' => $initialStock + $expectedDelta,
        ]);

        // Verify audit record was created
        $this->assertDatabaseHas('stock_ins', [
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'supplier_id' => $supplier->id,
            'quantity_received' => $quantityReceived,
            'unit_of_measure' => 'box',
            'unit_conversion' => $product->pieces_per_receiving_unit,
            'piece_delta' => $expectedDelta,
            'stock_before' => $initialStock,
            'stock_after' => $initialStock + $expectedDelta,
            'note' => 'PO #12345',
        ]);

        // Verify response includes expected data
        $response->assertJsonStructure([
            'message',
            'stock_in' => [
                'product_id',
                'product_name',
                'quantity_received',
                'unit_of_measure',
                'unit_conversion',
                'piece_delta',
                'stock_before',
                'stock_after',
                'supplier_id',
                'note',
            ],
            'product' => [
                'id',
                'name',
                'sku',
                'current_stock',
                // receiving_unit and pieces_per_receiving_unit should be present
            ],
        ]);
    }

    #[Test]
    public function stock_in_endpoint_works_without_supplier(): void
    {
        $admin = $this->createAdminUser();
        $product = $this->createProductForUser($admin);

        $initialStock = $product->current_stock;
        $quantityReceived = 2;
        $expectedDelta = $quantityReceived * $product->pieces_per_receiving_unit;

        $response = $this->actingAs($admin)->postJson('/api/inventory/stock-in', [
            'product_id' => $product->id,
            'quantity_received' => $quantityReceived,
            'unit_of_measure' => $product->receiving_unit,
            // No supplier_id provided
        ]);

        $response->assertStatus(201);

        // Verify stock increment
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'current_stock' => $initialStock + $expectedDelta,
        ]);

        // Verify audit record has null supplier_id
        $this->assertDatabaseHas('stock_ins', [
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'supplier_id' => null,
            'quantity_received' => $quantityReceived,
            'piece_delta' => $expectedDelta,
        ]);
    }

    #[Test]
    public function stock_in_endpoint_handles_sqlite_transaction_safely(): void
    {
        // This test ensures the transaction works without lockForUpdate on SQLite
        $admin = $this->createAdminUser();
        $product = $this->createProductForUser($admin);
        $expectedStock = $product->current_stock + $product->pieces_per_receiving_unit;

        $response = $this->actingAs($admin)->postJson('/api/inventory/stock-in', [
            'product_id' => $product->id,
            'quantity_received' => 1,
            'unit_of_measure' => $product->receiving_unit,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'current_stock' => $expectedStock,
        ]);
    }

    #[Test]
    public function get_products_includes_latest_supplier_and_received_at_data(): void
    {
        $admin = $this->createAdminUser();
        $product = $this->createProductForUser($admin);
        $supplier1 = $this->createActiveSupplier();
        $supplier2 = $this->createActiveSupplier();

        // Create two stock-in records for the same product
        StockIn::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'supplier_id' => $supplier1->id,
            'quantity_received' => 5,
            'unit_of_measure' => $product->receiving_unit,
        ]);

        // Wait a moment to ensure different timestamps
        sleep(1);

        StockIn::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'supplier_id' => $supplier2->id,
            'quantity_received' => 3,
            'unit_of_measure' => $product->receiving_unit,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/inventory/products');
        $response->assertStatus(200);

        $products = $response->json();
        $this->assertCount(1, $products);

        $productData = $products[0];
        $this->assertArrayHasKey('last_supplier_name', $productData);
        $this->assertArrayHasKey('last_received_at', $productData);
        $this->assertEquals($supplier2->name, $productData['last_supplier_name']);
        $this->assertNotNull($productData['last_received_at']);
    }
}