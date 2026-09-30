<?php

namespace Tests\Feature;

use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Stock-In / Receiving.
 *
 * BRD (Inventory Management) — "Manual stock adjustments (adding restocked
 * deliveries or deducting damaged/lost items)".
 *
 * REVISED: two corrections.
 *  1. A Staff request to the /stock-in PAGE is redirected by EnsureUserIsAdmin
 *     (the middleware redirects browser navigations and returns 403 only for
 *     JSON calls). The old test asserted 403 with a JSON body for a page
 *     request, which can never happen.
 *  2. The old "validates product ownership" test expected 404 when receiving
 *     stock for another user's product. The catalogue is SHARED store-wide, so
 *     ownership is irrelevant and the request must succeed.
 */
class StockInTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithStore;

    private function makeProductWithUnits(array $overrides = []): \App\Models\Product
    {
        return $this->makeProduct(array_merge([
            'category'                     => 'Test Category',
            'price'                        => 99.99,
            'current_stock'                => 10,
            'reorder_threshold'            => 5,
            'receiving_unit'               => 'box',
            'pieces_per_receiving_unit'   => 12, // 1 box = 12 pieces
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // Page access
    // -------------------------------------------------------------------------

    #[Test]
    public function guest_cannot_access_stock_in_page(): void
    {
        $this->get('/stock-in')->assertRedirect('/login');
    }

    #[Test]
    public function admin_can_access_stock_in_page(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/stock-in')
            ->assertOk()
            ->assertSee('Stock-In / Receiving');
    }

    /**
     * A Staff page request is redirected (not 403): EnsureUserIsAdmin only
     * returns a JSON 403 for API callers.
     */
    #[Test]
    public function cashier_cannot_access_stock_in_page(): void
    {
        $this->actingAs($this->makeCashier())
            ->get('/stock-in')
            ->assertRedirect(route('dashboard'));
    }

    // -------------------------------------------------------------------------
    // API authorization
    // -------------------------------------------------------------------------

    #[Test]
    public function guest_cannot_access_stock_in_api_endpoint(): void
    {
        $this->postJson('/api/inventory/stock-in', [])->assertUnauthorized();
    }

    #[Test]
    public function non_admin_cannot_access_stock_in_api_endpoint(): void
    {
        $this->actingAs($this->makeCashier())
            ->postJson('/api/inventory/stock-in', [])
            ->assertForbidden()
            ->assertJsonPath('message', 'Administrator access required.');
    }

    #[Test]
    public function stock_in_endpoint_validates_required_fields(): void
    {
        $this->actingAs($this->makeAdmin())
            ->postJson('/api/inventory/stock-in', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['product_id', 'quantity_received', 'unit_of_measure']);
    }

    /**
     * REVISED: was "validates product ownership" expecting 404. The catalogue is
     * shared store-wide, so any Admin may receive stock for any active item.
     */
    #[Test]
    public function admin_may_receive_stock_for_any_catalogue_item(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProductWithUnits();

        $this->actingAs($admin)->postJson('/api/inventory/stock-in', [
            'product_id'       => $product->id,
            'quantity_received'=> 1,
            'unit_of_measure'  => 'box',
        ])->assertCreated();
    }

    #[Test]
    public function stock_in_endpoint_validates_unit_of_measure_match(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProductWithUnits(['receiving_unit' => 'box']);

        $this->actingAs($admin)->postJson('/api/inventory/stock-in', [
            'product_id'       => $product->id,
            'quantity_received'=> 5,
            'unit_of_measure'  => 'piece', // mismatch
        ])->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Stock increment + audit trail
    // -------------------------------------------------------------------------

    #[Test]
    public function stock_in_creates_audit_record_and_increments_stock(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProductWithUnits(['current_stock' => 10]);
        $supplier = Supplier::factory()->create(['is_active' => true]);

        // 3 boxes x 12 pieces = 36 pieces
        $this->actingAs($admin)->postJson('/api/inventory/stock-in', [
            'product_id'        => $product->id,
            'supplier_id'       => $supplier->id,
            'quantity_received' => 3,
            'unit_of_measure'   => 'box',
            'note'              => 'PO #12345',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Stock received successfully');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'current_stock' => 46]);

        $this->assertDatabaseHas('stock_ins', [
            'product_id'        => $product->id,
            'user_id'           => $admin->id,
            'supplier_id'       => $supplier->id,
            'quantity_received' => 3,
            'unit_of_measure'   => 'box',
            'unit_conversion'   => 12,
            'piece_delta'       => 36,
            'stock_before'      => 10,
            'stock_after'       => 46,
            'note'              => 'PO #12345',
        ]);
    }

    #[Test]
    public function stock_in_works_without_a_supplier(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProductWithUnits(['current_stock' => 10]);

        $this->actingAs($admin)->postJson('/api/inventory/stock-in', [
            'product_id'        => $product->id,
            'quantity_received' => 2,
            'unit_of_measure'   => 'box',
        ])->assertCreated();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'current_stock' => 34]);

        $this->assertDatabaseHas('stock_ins', [
            'product_id'  => $product->id,
            'supplier_id' => null,
            'piece_delta' => 24,
        ]);
    }
}
