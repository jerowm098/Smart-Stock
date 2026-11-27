<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Automated verification for Jira SS Sprint 6:
 *  - SS-112: Inventory export payload accuracy (SS-25)
 *  - SS-129: Forgot / Reset password flow — valid, expired, invalid tokens (SS-120)
 *  - SS-142: Hardware category CRUD & blocked deletion when in use (SS-123)
 *  - SS-146: Supplier edit & archive preserving stock-in history (SS-124)
 */
class Sprint6Test extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'name' => 'Sprint Admin',
            'username' => 'sprintadmin',
            'email' => 'sprintadmin@example.com',
            'password' => Hash::make('123456'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_ss146_admin_can_edit_and_archive_supplier_preserving_stock_in_history(): void
    {
        $admin = $this->admin();

        $supplier = Supplier::create([
            'name' => 'Old Steel Supply',
            'contact_person' => 'Juan',
            'phone' => '0917-000-0001',
            'email' => 'old@example.com',
            'is_active' => true,
        ]);

        $product = Product::create([
            'user_id' => $admin->id,
            'name' => 'Steel Bar 10mm',
            'sku' => 'STE-BAR-10M',
            'category' => 'Building Materials',
            'price' => 150,
            'current_stock' => 20,
            'reorder_threshold' => 5,
            'receiving_unit' => 'piece',
            'is_active' => true,
        ]);

        $stockIn = StockIn::create([
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'user_id' => $admin->id,
            'quantity_received' => 20,
            'unit_of_measure' => 'piece',
            'unit_conversion' => 1,
            'piece_delta' => 20,
            'stock_before' => 0,
            'stock_after' => 20,
            'note' => 'Initial delivery',
        ]);

        // Edit supplier details (SS-144)
        $updateRes = $this->actingAs($admin)->putJson("/api/suppliers/{$supplier->id}", [
            'name' => 'Metro Steel Supply',
            'contact_person' => 'Pedro Santos',
            'phone' => '0917-999-8888',
            'email' => 'metro@example.com',
        ]);

        $updateRes->assertOk()
            ->assertJsonPath('supplier.name', 'Metro Steel Supply')
            ->assertJsonPath('supplier.contact_person', 'Pedro Santos');

        // Archive / soft-delete supplier (SS-145)
        $deleteRes = $this->actingAs($admin)->deleteJson("/api/suppliers/{$supplier->id}");
        $deleteRes->assertOk();

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'is_active' => false,
        ]);

        // Stock-in history remains intact
        $this->assertDatabaseHas('stock_ins', [
            'id' => $stockIn->id,
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_ss142_category_crud_and_blocked_deletion_when_in_use(): void
    {
        $admin = $this->admin();

        // Create category
        $createRes = $this->actingAs($admin)->postJson('/api/categories', [
            'name' => 'Power Tools',
            'description' => 'Drills and grinders',
        ]);
        $createRes->assertCreated()
            ->assertJsonPath('category.name', 'Power Tools');

        $categoryId = $createRes->json('category.id');

        // Link a product to this category
        $product = Product::create([
            'user_id' => $admin->id,
            'name' => 'Cordless Drill 18V',
            'sku' => 'COR-DRI-18V',
            'category' => 'Power Tools',
            'category_id' => $categoryId,
            'price' => 3200,
            'current_stock' => 10,
            'reorder_threshold' => 4,
            'receiving_unit' => 'piece',
            'is_active' => true,
        ]);

        // Rename category and verify product syncs
        $renameRes = $this->actingAs($admin)->putJson("/api/categories/{$categoryId}", [
            'name' => 'Heavy Power Tools',
            'description' => 'Updated description',
        ]);
        $renameRes->assertOk()
            ->assertJsonPath('category.name', 'Heavy Power Tools');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'category' => 'Heavy Power Tools',
            'category_id' => $categoryId,
        ]);

        // Deletion must be blocked (422) while product is assigned
        $blockedRes = $this->actingAs($admin)->deleteJson("/api/categories/{$categoryId}");
        $blockedRes->assertStatus(422);
        $this->assertDatabaseHas('categories', ['id' => $categoryId]);

        // Unassign product and delete successfully
        $product->update(['category' => null, 'category_id' => null]);
        $deleteRes = $this->actingAs($admin)->deleteJson("/api/categories/{$categoryId}");
        $deleteRes->assertOk();
        $this->assertDatabaseMissing('categories', ['id' => $categoryId]);
    }

    public function test_ss129_forgot_and_reset_password_valid_expired_and_invalid_tokens(): void
    {
        $user = User::factory()->create([
            'name' => 'Reset Tester',
            'username' => 'resettester',
            'email' => 'resettester@example.com',
            'password' => Hash::make('oldpass123'),
            'role' => 'cashier',
            'is_active' => true,
        ]);

        // 1. Valid forgot-password request
        $forgotRes = $this->postJson('/api/auth/forgot-password', [
            'identifier' => 'resettester@example.com',
        ]);
        $forgotRes->assertOk();
        $token = $forgotRes->json('reset_token');
        $this->assertNotEmpty($token);

        // 2. Invalid token rejected
        $invalidRes = $this->postJson('/api/auth/reset-password', [
            'token' => 'invalid-token-value',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ]);
        $invalidRes->assertStatus(422);

        // 3. Expired token rejected
        $user->forceFill(['reset_token_expires_at' => now()->subMinute()])->save();
        $expiredRes = $this->postJson('/api/auth/reset-password', [
            'token' => $token,
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ]);
        $expiredRes->assertStatus(422);

        // 4. Fresh valid token resets password and allows login
        $forgotAgain = $this->postJson('/api/auth/forgot-password', [
            'identifier' => 'resettester',
        ])->assertOk();
        $freshToken = $forgotAgain->json('reset_token');

        $resetRes = $this->postJson('/api/auth/reset-password', [
            'token' => $freshToken,
            'password' => 'newpass456',
            'password_confirmation' => 'newpass456',
        ]);
        $resetRes->assertOk();

        $this->assertTrue(Hash::check('newpass456', $user->fresh()->password));
        $this->assertNull($user->fresh()->reset_token);
    }

    public function test_ss112_inventory_catalogue_payload_supports_pdf_and_excel_export(): void
    {
        $admin = $this->admin();

        Product::create([
            'user_id' => $admin->id,
            'name' => 'Claw Hammer 16oz',
            'sku' => 'CLA-HAM-16O',
            'category' => 'Hand Tools',
            'price' => 245.00,
            'current_stock' => 24,
            'reorder_threshold' => 10,
            'receiving_unit' => 'piece',
            'is_active' => true,
        ]);

        $res = $this->actingAs($admin)->getJson('/api/inventory/products');
        $res->assertOk()
            ->assertJsonFragment([
                'name' => 'Claw Hammer 16oz',
                'sku' => 'CLA-HAM-16O',
                'category' => 'Hand Tools',
                'receiving_unit' => 'piece',
                'current_stock' => 24,
                'reorder_threshold' => 10,
            ]);
    }
}
