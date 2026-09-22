<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * QA tests for per-account inventory isolation.
 *
 * Covers:
 *  - Each user only sees their own products
 *  - New accounts start with an empty inventory
 *  - Users cannot delete products they do not own
 *  - Alerts are scoped to the authenticated user
 */
class InventoryIsolationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_only_sees_their_own_products(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        Product::factory()->count(3)->create(['user_id' => $ownerA->id]);
        Product::factory()->count(2)->create(['user_id' => $ownerB->id]);

        $responseA = $this->actingAs($ownerA)->getJson('/api/inventory/products');
        $responseB = $this->actingAs($ownerB)->getJson('/api/inventory/products');

        $responseA->assertStatus(200)->assertJsonCount(3);
        $responseB->assertStatus(200)->assertJsonCount(2);
    }

    #[Test]
    public function newly_created_account_starts_with_empty_inventory(): void
    {
        $existingUser = User::factory()->create();
        Product::factory()->count(5)->create(['user_id' => $existingUser->id]);

        $newUser = User::factory()->create();

        $response = $this->actingAs($newUser)->getJson('/api/inventory/products');

        $response->assertStatus(200)->assertJsonCount(0);
    }

    #[Test]
    public function user_cannot_delete_a_product_they_do_not_own(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        $product = Product::factory()->create(['user_id' => $ownerA->id]);

        $response = $this->actingAs($ownerB)->deleteJson("/api/inventory/{$product->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'user_id' => $ownerA->id]);
    }

    #[Test]
    public function owner_can_delete_their_own_product(): void
    {
        $owner = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner)->deleteJson("/api/inventory/{$product->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    #[Test]
    public function alerts_are_scoped_to_the_authenticated_user(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        // Low stock product owned by A
        Product::factory()->create([
            'user_id' => $ownerA->id,
            'current_stock' => 1,
            'reorder_threshold' => 5,
        ]);

        // Low stock product owned by B
        Product::factory()->create([
            'user_id' => $ownerB->id,
            'current_stock' => 2,
            'reorder_threshold' => 5,
        ]);

        $responseA = $this->actingAs($ownerA)->getJson('/api/inventory/alerts');
        $responseB = $this->actingAs($ownerB)->getJson('/api/inventory/alerts');

        $responseA->assertStatus(200)->assertJsonCount(1);
        $responseB->assertStatus(200)->assertJsonCount(1);

        $this->assertSame($ownerA->id, $responseA->json(0)['user_id']);
        $this->assertSame($ownerB->id, $responseB->json(0)['user_id']);
    }

    #[Test]
    public function stored_product_is_owned_by_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/inventory/add', [
            'name' => 'Test Widget',
            'sku' => 'TW-001',
            'category' => 'Widgets',
            'price' => 99.50,
            'current_stock' => 10,
            'reorder_threshold' => 3,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', [
            'sku' => 'TW-001',
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function guest_cannot_access_inventory_api(): void
    {
        $this->getJson('/api/inventory/products')->assertStatus(401);
        $this->getJson('/api/inventory/alerts')->assertStatus(401);
        $this->postJson('/api/inventory/add', [
            'name' => 'Nope',
            'sku' => 'NP-001',
            'price' => 1,
            'current_stock' => 1,
            'reorder_threshold' => 1,
        ])->assertStatus(401);
    }
}
