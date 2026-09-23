<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PosCheckoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_cannot_access_pos_checkout_page(): void
    {
        $response = $this->get('/pos');
        $response->assertRedirect('/login');
    }

    #[Test]
    public function authenticated_user_can_access_pos_checkout_page(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        $response = $this->actingAs($user)->get('/pos');
        $response->assertStatus(200);
        $response->assertSee('POS Checkout');
    }

    #[Test]
    public function pos_checkout_requires_authentication_for_api(): void
    {
        $response = $this->postJson('/api/pos/checkout', []);
        $response->assertStatus(401);
    }

    #[Test]
    public function pos_checkout_validates_request_data(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        $response = $this->actingAs($user)->postJson('/api/pos/checkout', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cart_items', 'payment_amount']);
    }

    #[Test]
    public function pos_checkout_processes_valid_transaction(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        
        // Create products owned by the user
        $product1 = Product::factory()->create([
            'user_id' => $user->id,
            'price' => 100.00,
            'current_stock' => 10,
        ]);
        
        $product2 = Product::factory()->create([
            'user_id' => $user->id,
            'price' => 50.00,
            'current_stock' => 5,
        ]);

        // Perform checkout
        $response = $this->actingAs($user)->postJson('/api/pos/checkout', [
            'cart_items' => [
                ['product_id' => $product1->id, 'quantity' => 2],
                ['product_id' => $product2->id, 'quantity' => 1],
            ],
            'payment_amount' => 300.00,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Checkout completed successfully.',
        ]);

        // Verify sale was created
        $this->assertDatabaseHas('sales', [
            'user_id' => $user->id,
            'total_amount' => 280.00, // subtotal 250 + 12% tax 30
            'payment_amount' => 300.00,
            'change_amount' => 20.00,
        ]);

        // Verify sale items were created
        $sale = Sale::where('user_id', $user->id)->latest()->first();
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'line_total' => 200.00,
        ]);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'unit_price' => 50.00,
            'line_total' => 50.00,
        ]);

        // Verify stock was deducted
        $this->assertDatabaseHas('products', [
            'id' => $product1->id,
            'current_stock' => 8, // 10 - 2
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product2->id,
            'current_stock' => 4, // 5 - 1
        ]);
    }

    #[Test]
    public function pos_checkout_rejects_insufficient_stock(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        
        $product = Product::factory()->create([
            'user_id' => $user->id,
            'price' => 100.00,
            'current_stock' => 1,
        ]);

        $response = $this->actingAs($user)->postJson('/api/pos/checkout', [
            'cart_items' => [
                ['product_id' => $product->id, 'quantity' => 5], // More than available
            ],
            'payment_amount' => 500.00,
        ]);

        $response->assertStatus(409);
        $response->assertJson([
            'message' => 'Insufficient stock.',
        ]);
        $response->assertJsonStructure([
            'message',
            'product_id',
            'product_name',
            'available_stock',
            'requested_quantity',
        ]);
    }

    #[Test]
    public function pos_checkout_rejects_insufficient_payment(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        
        $product = Product::factory()->create([
            'user_id' => $user->id,
            'price' => 100.00,
            'current_stock' => 10,
        ]);

        $response = $this->actingAs($user)->postJson('/api/pos/checkout', [
            'cart_items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'payment_amount' => 150.00, // Less than total (200 + tax)
        ]);

        $response->assertStatus(402);
        $response->assertJson([
            'message' => 'Payment amount is insufficient.',
        ]);
        $response->assertJsonStructure([
            'message',
            'total_amount',
            'payment_amount',
        ]);
    }

    #[Test]
    public function pos_checkout_prevents_selling_other_users_products(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        $otherUser = User::factory()->create(['role' => 'cashier']);
        
        $product = Product::factory()->create([
            'user_id' => $otherUser->id, // Owned by other user
            'price' => 100.00,
            'current_stock' => 10,
        ]);

        $response = $this->actingAs($user)->postJson('/api/pos/checkout', [
            'cart_items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'payment_amount' => 200.00,
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Unauthorized: Product does not belong to you.',
        ]);
        $response->assertJsonStructure([
            'message',
            'product_id',
        ]);
    }

    #[Test]
    public function pos_checkout_handles_duplicate_product_selection(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        
        $product = Product::factory()->create([
            'user_id' => $user->id,
            'price' => 100.00,
            'current_stock' => 10,
        ]);

        $response = $this->actingAs($user)->postJson('/api/pos/checkout', [
            'cart_items' => [
                ['product_id' => $product->id, 'quantity' => 2],
                ['product_id' => $product->id, 'quantity' => 3], // Same product again
            ],
            'payment_amount' => 600.00,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Checkout completed successfully.',
        ]);

        // Verify sale was created with correct quantity (5 total)
        $sale = Sale::where('user_id', $user->id)->latest()->first();
        $this->assertEquals(560.00, $sale->total_amount); // 5 * 100 + 12% tax
        $this->assertEquals(600.00, $sale->payment_amount);
        $this->assertEquals(40.00, $sale->change_amount);

        // Verify single sale item with quantity 5
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 100.00,
            'line_total' => 500.00,
        ]);

        // Verify stock was deducted correctly
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'current_stock' => 5, // 10 - 5
        ]);
    }

    #[Test]
    public function pos_checkout_includes_tax_calculation(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        
        $product = Product::factory()->create([
            'user_id' => $user->id,
            'price' => 100.00,
            'current_stock' => 10,
        ]);

        $response = $this->actingAs($user)->postJson('/api/pos/checkout', [
            'cart_items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'payment_amount' => 200.00,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Checkout completed successfully.',
        ]);

        // Verify tax calculation (12% of 100 = 12, total = 112)
        $sale = Sale::where('user_id', $user->id)->latest()->first();
        $this->assertEquals(112.00, $sale->total_amount);
        
        $response->assertJsonStructure([
            'message',
            'sale_id',
            'subtotal_amount',
            'tax_amount',
            'total_amount',
            'payment_amount',
            'change_amount',
            'change',
        ]);
        
        $response->assertJson([
            'subtotal_amount' => 100.00,
            'tax_amount' => 12.00,
            'total_amount' => 112.00,
            'payment_amount' => 200.00,
            'change_amount' => 88.00,
        ]);
    }
}