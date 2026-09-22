<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * SS-67 / Test Plan TC-04: Verify accurate column rendering and data alignment
 * from API to UI.
 *
 * The Products and Overview pages render their rows in the browser, so this
 * test verifies the complete contract in two parts:
 *  - the API returns the exact database values; and
 *  - the Blade templates render the required columns and map those API fields
 *    to the corresponding table cells.
 */
class InventoryColumnRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function inventory_api_returns_exact_database_values_for_required_columns(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'user_id' => $user->id,
            'name' => 'Wireless Mouse',
            'sku' => 'WM-2048',
            'category' => 'Accessories',
            'price' => 49.99,
            'current_stock' => 12,
            'reorder_threshold' => 5,
        ]);

        $response = $this->actingAs($user)->getJson('/api/inventory/products');

        $response->assertOk()->assertJsonCount(1);

        $this->assertSame($product->sku, $response->json(0)['sku']);
        $this->assertSame($product->name, $response->json(0)['name']);
        $this->assertSame($product->category, $response->json(0)['category']);
        $this->assertSame(number_format($product->price, 2), $response->json(0)['price']);
        $this->assertSame($product->current_stock, $response->json(0)['current_stock']);
    }

    #[Test]
    public function products_page_declares_required_columns_and_maps_api_fields_to_cells(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/products');

        $response->assertOk();

        $html = $response->getContent();

        // Required table columns.
        $this->assertStringContainsString('<th>SKU / Code</th>', $html);
        $this->assertStringContainsString('<th>Item Name</th>', $html);
        $this->assertStringContainsString('<th>Category</th>', $html);
        $this->assertStringContainsString('<th>Unit Price</th>', $html);
        $this->assertStringContainsString('<th>Current Stock Count</th>', $html);

        // The browser renderer must map each API field to the matching cell.
        $this->assertStringContainsString('<td>${escapeHtml(p.sku)}</td>', $html);
        $this->assertStringContainsString('<td><strong>${escapeHtml(p.name)}</strong></td>', $html);
        $this->assertStringContainsString('<td>${escapeHtml(p.category || \'—\')}</td>', $html);
        $this->assertStringContainsString('<td>₱${parseFloat(p.price).toFixed(2)}</td>', $html);
        $this->assertStringContainsString('<td class="stock-cell stock-${s.class}">${p.current_stock}</td>', $html);
        $this->assertStringContainsString("fetch('/api/inventory/products')", $html);
    }

    #[Test]
    public function overview_page_declares_required_columns_and_maps_api_fields_to_cells(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();

        $html = $response->getContent();

        // Required snapshot table columns.
        $this->assertStringContainsString('<th>SKU / Code</th>', $html);
        $this->assertStringContainsString('<th>Item Name</th>', $html);
        $this->assertStringContainsString('<th>Category</th>', $html);
        $this->assertStringContainsString('<th>Unit Price</th>', $html);
        $this->assertStringContainsString('<th>Current Stock Count</th>', $html);

        // The browser renderer must map each API field to the matching cell.
        $this->assertStringContainsString('<td>${escapeHtml(p.sku)}</td>', $html);
        $this->assertStringContainsString('<td><strong>${escapeHtml(p.name)}</strong></td>', $html);
        $this->assertStringContainsString('<td>${escapeHtml(p.category || \'—\')}</td>', $html);
        $this->assertStringContainsString('<td>₱${parseFloat(p.price).toFixed(2)}</td>', $html);
        $this->assertStringContainsString('<td class="stock-cell stock-${s.class}">${p.current_stock}</td>', $html);
        $this->assertStringContainsString("fetch('/api/inventory/products')", $html);
    }

    #[Test]
    public function overview_page_renders_status_logic_from_the_same_stock_value(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();

        $html = $response->getContent();

        // Status is derived from the same stock value rendered in the stock
        // count column, preventing a mismatch between the two columns.
        $this->assertStringContainsString('const s = getStatus(p.current_stock, p.reorder_threshold);', $html);
        $this->assertStringContainsString('<td class="stock-cell stock-${s.class}">${p.current_stock}</td>', $html);
        $this->assertStringContainsString('<td><span class="stock-badge ${s.class}">${s.label}</span></td>', $html);
    }

    /**
     * SS-18: Verify the category dropdown filter is present and wired
     * into the real-time filtering pipeline.
     */
    #[Test]
    public function products_page_renders_category_dropdown_filter(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/products');

        $response->assertOk();

        $html = $response->getContent();

        // The dedicated category dropdown element.
        $this->assertStringContainsString('id="categoryFilter"', $html);
        $this->assertStringContainsString('class="category-select"', $html);
        $this->assertStringContainsString('onchange="filterProducts()"', $html);
        $this->assertStringContainsString('<option value="">All Categories</option>', $html);

        // The populate function builds the option list from loaded products.
        $this->assertStringContainsString('function populateCategoryFilter()', $html);
        $this->assertStringContainsString("document.getElementById('categoryFilter')", $html);

        // The filter function reads the selected category and applies it.
        $this->assertStringContainsString("document.getElementById('categoryFilter').value", $html);
        $this->assertStringContainsString('p.category || \'\').trim().toLowerCase()', $html);
    }
}