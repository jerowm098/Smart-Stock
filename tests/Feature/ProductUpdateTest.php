<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * SS-21 / SS-82 through SS-86: Tests for updating existing product details.
 *
 * Covers:
 *  - SS-82: Edit product modal retrieves existing item data (PUT /api/inventory/update).
 *  - SS-83: Frontend field validations prevent negative prices or blank fields (server-side mirrors).
 *  - SS-84: PUT /api/inventory/update endpoint exists and responds with product data.
 *  - SS-85: Database record is updated for the selected product ID.
 *  - SS-86: Successful edit flow refreshes the catalogue table from the updated API data.
 */
class ProductUpdateTest extends TestCase
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
    // SS-84: PUT /api/inventory/update endpoint
    // -------------------------------------------------------------------------

    #[Test]
    public function update_endpoint_returns_401_for_guest(): void
    {
        $this->putJson('/api/inventory/update', [
            'id' => 1,
            'name' => 'Updated',
            'sku' => 'WM-UPD',
            'price' => 10,
            'current_stock' => 5,
            'reorder_threshold' => 1,
        ])->assertStatus(401);
    }

    #[Test]
    public function update_endpoint_requires_id(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/inventory/update', [
                'name' => 'Updated',
                'sku' => 'WM-UPD',
                'price' => 10,
                'current_stock' => 5,
                'reorder_threshold' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('id');
    }

    #[Test]
    public function update_endpoint_returns_404_for_nonexistent_product(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/inventory/update', [
                'id' => 9999,
                'name' => 'Updated',
                'sku' => 'WM-UPD',
                'price' => 10,
                'current_stock' => 5,
                'reorder_threshold' => 1,
            ])
            ->assertStatus(404)
            ->assertJsonPath('message', 'Product not found or access denied.');
    }

    #[Test]
    public function update_endpoint_returns_404_for_other_users_product(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $product = $this->createProduct($ownerA);

        $this->actingAs($ownerB)
            ->putJson('/api/inventory/update', [
                'id' => $product->id,
                'name' => 'Hacked',
                'sku' => 'WM-HACK',
                'price' => 1,
                'current_stock' => 1,
                'reorder_threshold' => 1,
            ])
            ->assertStatus(404);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'user_id' => $ownerA->id,
            'name' => 'Wireless Mouse',
        ]);
    }

    #[Test]
    public function update_endpoint_updates_product_and_returns_product(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user);

        $response = $this->actingAs($user)->putJson('/api/inventory/update', [
            'id' => $product->id,
            'name' => 'Gaming Mouse',
            'sku' => 'WM-002',
            'category' => 'Gaming',
            'price' => 49.99,
            'current_stock' => 30,
            'reorder_threshold' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Product updated successfully')
            ->assertJsonPath('product.id', $product->id)
            ->assertJsonPath('product.name', 'Gaming Mouse')
            ->assertJsonPath('product.sku', 'WM-002')
            ->assertJsonPath('product.category', 'Gaming')
            ->assertJsonPath('product.price', '49.99')
            ->assertJsonPath('product.current_stock', 30)
            ->assertJsonPath('product.reorder_threshold', 5);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Gaming Mouse',
            'sku' => 'WM-002',
            'category' => 'Gaming',
            'price' => 49.99,
            'current_stock' => 30,
            'reorder_threshold' => 5,
        ]);
    }

    // -------------------------------------------------------------------------
    // SS-83: Validation prevents negative prices or blank fields
    // -------------------------------------------------------------------------

    #[Test]
    public function update_rejects_blank_product_name(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user);

        $this->actingAs($user)
            ->putJson('/api/inventory/update', [
                'id' => $product->id,
                'name' => '',
                'sku' => $product->sku,
                'price' => $product->price,
                'current_stock' => $product->current_stock,
                'reorder_threshold' => $product->reorder_threshold,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    #[Test]
    public function update_rejects_blank_sku(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user);

        $this->actingAs($user)
            ->putJson('/api/inventory/update', [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => '',
                'price' => $product->price,
                'current_stock' => $product->current_stock,
                'reorder_threshold' => $product->reorder_threshold,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('sku');
    }

    #[Test]
    public function update_rejects_negative_price(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user);

        $this->actingAs($user)
            ->putJson('/api/inventory/update', [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => -5,
                'current_stock' => $product->current_stock,
                'reorder_threshold' => $product->reorder_threshold,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('price');
    }

    #[Test]
    public function update_rejects_negative_current_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user);

        $this->actingAs($user)
            ->putJson('/api/inventory/update', [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'current_stock' => -1,
                'reorder_threshold' => $product->reorder_threshold,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_stock');
    }

    #[Test]
    public function update_rejects_negative_reorder_threshold(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user);

        $this->actingAs($user)
            ->putJson('/api/inventory/update', [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'current_stock' => $product->current_stock,
                'reorder_threshold' => -1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reorder_threshold');
    }

    #[Test]
    public function update_rejects_non_numeric_price(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user);

        $this->actingAs($user)
            ->putJson('/api/inventory/update', [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => 'abc',
                'current_stock' => $product->current_stock,
                'reorder_threshold' => $product->reorder_threshold,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('price');
    }

    #[Test]
    public function update_rejects_duplicate_sku_for_same_user(): void
    {
        $user = User::factory()->create();
        $this->createProduct($user); // WM-001

        $product2 = Product::factory()->create([
            'user_id' => $user->id,
            'name' => 'Keyboard',
            'sku' => 'KB-001',
            'price' => 59.99,
            'current_stock' => 20,
            'reorder_threshold' => 5,
        ]);

        $this->actingAs($user)
            ->putJson('/api/inventory/update', [
                'id' => $product2->id,
                'name' => $product2->name,
                'sku' => 'WM-001',
                'price' => $product2->price,
                'current_stock' => $product2->current_stock,
                'reorder_threshold' => $product2->reorder_threshold,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('sku');
    }

    // -------------------------------------------------------------------------
    // SS-82: Edit product modal pre-filled with existing item attributes
    // -------------------------------------------------------------------------

    #[Test]
    public function products_page_declares_edit_modal_and_edit_button(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user);

        $response = $this->actingAs($user)->get('/products');

        $response->assertOk();
        $html = $response->getContent();

        // Edit modal exists with the required input fields.
        $this->assertStringContainsString('id="editModal"', $html);
        $this->assertStringContainsString('id="eId"', $html);
        $this->assertStringContainsString('id="eName"', $html);
        $this->assertStringContainsString('id="eSku"', $html);
        $this->assertStringContainsString('id="ePrice"', $html);
        $this->assertStringContainsString('id="eStock"', $html);
        $this->assertStringContainsString('id="eThreshold"', $html);

        // Edit button is wired to openEditModal with the product id.
        $this->assertStringContainsString('class="btn-edit"', $html);
        $this->assertStringContainsString('openEditModal(', $html);

        // The modal pre-fill function reads existing item attributes.
        $this->assertStringContainsString('function openEditModal(', $html);
        $this->assertStringContainsString("document.getElementById('eName').value = product.name || '';", $html);
        $this->assertStringContainsString("document.getElementById('eSku').value = product.sku || '';", $html);
        $this->assertStringContainsString("document.getElementById('ePrice').value = product.price || '';", $html);
        $this->assertStringContainsString("document.getElementById('eStock').value = product.current_stock || '';", $html);

        // Frontend validation function exists.
        $this->assertStringContainsString('function validateEditForm(', $html);
        $this->assertStringContainsString('function handleEditProduct(', $html);
        $this->assertStringContainsString("fetch('/api/inventory/update'", $html);
    }

    // -------------------------------------------------------------------------
    // Role-based action button visibility on the Products page.
    // -------------------------------------------------------------------------

    #[Test]
    public function admin_products_page_hides_edit_and_delete_buttons(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createProduct($admin);

        $response = $this->actingAs($admin)->get('/products');

        $response->assertOk();
        $html = $response->getContent();

        // Admin role flag: adjustment allowed, product management disabled.
        $this->assertStringContainsString("const canAdjustStock = true;", $html);
        $this->assertStringContainsString("const canManageProducts = false;", $html);

        // Admin rows must only render the Adjust button.
        $this->assertStringContainsString(
            "canManageProducts ? '<button class=\"btn-edit\" onclick=\"openEditModal(' + p.id + ')\">Edit</button>' : ''",
            $html
        );
        $this->assertStringContainsString(
            "canAdjustStock ? '<button class=\"btn-adjust\" onclick=\"openAdjustModal(' + p.id + ', ' + p.current_stock + ')\">Adjust</button>' : ''",
            $html
        );
        $this->assertStringContainsString(
            "canManageProducts ? '<button class=\"btn-delete\" onclick=\"deleteProduct(' + p.id + ')\">Delete</button>' : ''",
            $html
        );
    }

    #[Test]
    public function cashier_products_page_hides_adjust_button(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->createProduct($cashier);

        $response = $this->actingAs($cashier)->get('/products');

        $response->assertOk();
        $html = $response->getContent();

        // Cashier role flag: product management allowed, adjustment disabled.
        $this->assertStringContainsString("const canAdjustStock = false;", $html);
        $this->assertStringContainsString("const canManageProducts = true;", $html);

        // Cashier rows must only render Edit and Delete buttons.
        $this->assertStringContainsString(
            "canManageProducts ? '<button class=\"btn-edit\" onclick=\"openEditModal(' + p.id + ')\">Edit</button>' : ''",
            $html
        );
        $this->assertStringContainsString(
            "canAdjustStock ? '<button class=\"btn-adjust\" onclick=\"openAdjustModal(' + p.id + ', ' + p.current_stock + ')\">Adjust</button>' : ''",
            $html
        );
        $this->assertStringContainsString(
            "canManageProducts ? '<button class=\"btn-delete\" onclick=\"deleteProduct(' + p.id + ')\">Delete</button>' : ''",
            $html
        );
    }

    // -------------------------------------------------------------------------
    // SS-86: Successful edit flow refreshes the catalogue table
    // -------------------------------------------------------------------------

    #[Test]
    public function editing_product_details_refreshes_catalogue_table_from_updated_api_data(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user);

        $this->actingAs($user)
            ->putJson('/api/inventory/update', [
                'id' => $product->id,
                'name' => 'Updated Mouse',
                'sku' => 'WM-UPDATED',
                'category' => 'Updated Category',
                'price' => 39.99,
                'current_stock' => 25,
                'reorder_threshold' => 6,
            ])
            ->assertStatus(200);

        $apiResponse = $this->actingAs($user)->getJson('/api/inventory/products');
        $apiResponse->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Updated Mouse')
            ->assertJsonPath('0.sku', 'WM-UPDATED')
            ->assertJsonPath('0.price', '39.99')
            ->assertJsonPath('0.current_stock', 25)
            ->assertJsonPath('0.reorder_threshold', 6);

        $html = $this->actingAs($user)->get('/products')->getContent();

        // The successful edit handler must reload products so the rendered table
        // reflects the updated API response.
        $this->assertStringContainsString(
            "showToast('Product updated successfully!', 'success');\n                    loadProducts();",
            $html
        );
        $this->assertStringContainsString("fetch('/api/inventory/products')", $html);
    }

    // -------------------------------------------------------------------------
    // SS-85: SQL update executes for selected product ID
    // -------------------------------------------------------------------------

    #[Test]
    public function update_executes_sql_update_for_selected_product_id(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user);

        $this->actingAs($user)->putJson('/api/inventory/update', [
            'id' => $product->id,
            'name' => 'Updated Name',
            'sku' => 'WM-UPDATED',
            'category' => 'Updated Category',
            'price' => 99.99,
            'current_stock' => 100,
            'reorder_threshold' => 20,
        ])->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Name',
            'sku' => 'WM-UPDATED',
            'category' => 'Updated Category',
            'price' => 99.99,
            'current_stock' => 100,
            'reorder_threshold' => 20,
            'user_id' => $user->id,
        ]);

        // Verify the product object was not replaced by a different row.
        $this->assertSame($product->id, Product::find($product->id)->id);
    }

    #[Test]
    public function update_in_one_account_does_not_affect_another_account(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        $productA = $this->createProduct($ownerA, ['name' => 'Product A', 'sku' => 'PA-001', 'price' => 10, 'current_stock' => 10, 'reorder_threshold' => 2]);
        $productB = $this->createProduct($ownerB, ['name' => 'Product B', 'sku' => 'PB-001', 'price' => 20, 'current_stock' => 20, 'reorder_threshold' => 4]);

        $this->actingAs($ownerA)
            ->putJson('/api/inventory/update', [
                'id' => $productA->id,
                'name' => 'Product A Updated',
                'sku' => 'PA-UPDATED',
                'price' => 15,
                'current_stock' => 15,
                'reorder_threshold' => 3,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'id' => $productA->id,
            'name' => 'Product A Updated',
            'sku' => 'PA-UPDATED',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $productB->id,
            'name' => 'Product B',
            'sku' => 'PB-001',
            'price' => 20,
        ]);
    }
}
