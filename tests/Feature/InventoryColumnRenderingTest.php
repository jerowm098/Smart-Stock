<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Column rendering and data alignment from API to UI.
 *
 * The Products and Overview pages render their rows in the browser, so this
 * test verifies the complete contract in two parts:
 *  - the API returns the exact database values; and
 *  - the Blade templates render the required columns and map those API fields
 *    to the corresponding table cells.
 *
 * REVISED: these pages are Admin-only under the BRD role model
 * ("Staff Role = POS Access Only"), so the tests act as an Admin.
 */
class InventoryColumnRenderingTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithStore;

    #[Test]
    public function inventory_api_returns_exact_database_values_for_required_columns(): void
    {
        $admin = $this->makeAdmin();

        $product = $this->makeProduct([
            'name'              => 'Wireless Mouse',
            'sku'               => 'WM-2048',
            'category'          => 'Accessories',
            'price'             => 49.99,
            'current_stock'     => 12,
            'reorder_threshold' => 5,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/inventory/products');

        $response->assertOk()->assertJsonCount(1);

        $row = $response->json(0);

        $this->assertSame($product->sku, $row['sku']);
        $this->assertSame($product->name, $row['name']);
        $this->assertSame($product->category, $row['category']);
        $this->assertSame(number_format($product->price, 2), $row['price']);
        $this->assertSame($product->current_stock, $row['current_stock']);
    }

    #[Test]
    public function products_page_declares_required_columns_and_maps_api_fields_to_cells(): void
    {
        $response = $this->actingAs($this->makeAdmin())->get('/products');

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
        $this->assertStringContainsString("<td>\${escapeHtml(p.category || '—')}</td>", $html);
        $this->assertStringContainsString('<td>₱${parseFloat(p.price).toFixed(2)}</td>', $html);
        $this->assertStringContainsString('<td class="stock-cell stock-${s.class}">${p.current_stock}</td>', $html);
        $this->assertStringContainsString("fetch('/api/inventory/products')", $html);
    }

    /**
     * NOTE: /dashboard renders resources/views/overview.blade.php
     * (InventoryController::index), not dashboard.blade.php. The product
     * column contract therefore belongs to /products, which is asserted above;
     * here we only verify the overview renders for an Admin and that its
     * suggestions table is wired to the forecast-backed endpoint.
     */
    #[Test]
    public function overview_page_renders_for_an_admin_and_reads_the_forecast_endpoint(): void
    {
        $response = $this->actingAs($this->makeAdmin())->get('/dashboard');

        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('Order Suggestions', $html);
        $this->assertStringContainsString("fetch('/api/dashboard/restock-suggestions')", $html);
    }

    /**
     * Status on the product grid must be derived from the same stock value that
     * is rendered in the stock column, so the two can never disagree.
     */
    #[Test]
    public function products_page_renders_status_from_the_same_stock_value(): void
    {
        $response = $this->actingAs($this->makeAdmin())->get('/products');

        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString(
            'const s = getStatus(p.current_stock, p.reorder_threshold);',
            $html
        );
        $this->assertStringContainsString(
            '<td class="stock-cell stock-${s.class}">${p.current_stock}</td>',
            $html
        );
    }

    /**
     * The POS page must use the cashier-safe catalog endpoint, not the
     * Admin-only inventory endpoint, otherwise Staff cannot load products.
     */
    #[Test]
    public function pos_page_uses_the_cashier_safe_catalog_endpoint(): void
    {
        $response = $this->actingAs($this->makeCashier())->get('/pos');

        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString("fetch('/api/pos/products')", $html);
        $this->assertStringNotContainsString("fetch('/api/inventory/products')", $html);
    }
}