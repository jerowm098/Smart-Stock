<?php

namespace Tests\Feature;

use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * POS checkout behaviour.
 *
 * REVISED (was: PosCheckoutTest).
 * Two corrections against the BRD:
 *
 *  1. Totals. The old test asserted `total_amount = 280` for a 250 subtotal,
 *     with the comment "subtotal 250 + 12% tax 30". BRD (Point of Sale) —
 *     Business rules states:
 *         "Cart Total = Sum of (Item Unit Price * Quantity)."
 *     and the server applies no tax multiplier. (Transaction Tracking also
 *     notes "Revenue tracking is based purely on the gross total of items
 *     sold".) The stale 12% expectation is corrected to the plain subtotal.
 *
 *  2. Ownership. The old "prevents selling other users products" test assumed
 *     per-account products. The catalogue is SHARED store-wide, so any Staff
 *     member may sell any active item; the real guard is the role + the
 *     deactivation flag.
 */
class PosCheckoutTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithStore;

    #[Test]
    public function guest_cannot_access_pos_checkout_page(): void
    {
        $this->get('/pos')->assertRedirect('/login');
    }

    /**
     * BRD: "Admin Role = POS + Inventory + ..." so Admins may also sell.
     */
    #[Test]
    public function admin_can_access_pos_checkout_page(): void
    {
        $this->actingAs($this->makeAdmin())->get('/pos')->assertOk();
    }

    #[Test]
    public function cashier_can_access_pos_checkout_page(): void
    {
        $this->actingAs($this->makeCashier())
            ->get('/pos')
            ->assertOk()
            ->assertSee('POS Checkout');
    }

    #[Test]
    public function pos_checkout_requires_authentication_for_api(): void
    {
        $this->postJson('/api/pos/checkout', [])->assertUnauthorized();
    }

    #[Test]
    public function pos_checkout_validates_request_data(): void
    {
        $this->actingAs($this->makeCashier())
            ->postJson('/api/pos/checkout', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cart_items', 'payment_amount']);
    }

    #[Test]
    public function pos_checkout_processes_valid_transaction(): void
    {
        $cashier = $this->makeCashier();

        $product1 = $this->makeProduct(['price' => 100.00, 'current_stock' => 10]);
        $product2 = $this->makeProduct(['price' => 50.00, 'current_stock' => 5]);

        $response = $this->actingAs($cashier)->postJson('/api/pos/checkout', [
            'cart_items' => [
                ['product_id' => $product1->id, 'quantity' => 2], // 200
                ['product_id' => $product2->id, 'quantity' => 1], //  50
            ],
            'payment_amount' => 300.00,
        ]);

        $response->assertOk()->assertJsonPath('message', 'Checkout completed successfully.');

        // BRD business rule: total is the plain sum of price x quantity (250).
        $this->assertSame(250.0, (float) $response->json('total_amount'));
        $this->assertSame(250.0, (float) $response->json('subtotal_amount'));
        $this->assertSame(50.0, (float) $response->json('change_amount'));

        $this->assertDatabaseHas('sales', [
            'user_id'        => $cashier->id,
            'total_amount'   => 250.00,
            'payment_amount' => 300.00,
            'change_amount'  => 50.00,
        ]);

        $sale = Sale::where('user_id', $cashier->id)->latest('id')->first();

        $this->assertDatabaseHas('sale_items', [
            'sale_id'    => $sale->id,
            'product_id' => $product1->id,
            'quantity'   => 2,
            'unit_price' => 100.00,
            'line_total' => 200.00,
        ]);
        $this->assertDatabaseHas('sale_items', [
            'sale_id'    => $sale->id,
            'product_id' => $product2->id,
            'quantity'   => 1,
            'unit_price' => 50.00,
            'line_total' => 50.00,
        ]);

        // Stock deducted.
        $this->assertSame(8, (int) $product1->fresh()->current_stock);
        $this->assertSame(4, (int) $product2->fresh()->current_stock);
    }

    /**
     * BRD: "The system shall prevent the user from adding a quantity of an item
     * that exceeds the current available stock."
     */
    #[Test]
    public function pos_checkout_rejects_insufficient_stock(): void
    {
        $cashier = $this->makeCashier();
        $product = $this->makeProduct(['price' => 100.00, 'current_stock' => 1]);

        $this->actingAs($cashier)->postJson('/api/pos/checkout', [
            'cart_items' => [['product_id' => $product->id, 'quantity' => 5]],
            'payment_amount' => 500.00,
        ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Insufficient stock.')
            ->assertJsonStructure(['message', 'product_id', 'product_name', 'available_stock', 'requested_quantity']);

        $this->assertSame(1, (int) $product->fresh()->current_stock);
    }

    #[Test]
    public function pos_checkout_rejects_insufficient_payment(): void
    {
        $cashier = $this->makeCashier();
        $product = $this->makeProduct(['price' => 100.00, 'current_stock' => 10]);

        $this->actingAs($cashier)->postJson('/api/pos/checkout', [
            'cart_items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_amount' => 150.00, // total is 200
        ])
            ->assertStatus(402)
            ->assertJsonPath('message', 'Payment amount is insufficient.')
            ->assertJsonStructure(['message', 'total_amount', 'payment_amount']);

        $this->assertDatabaseCount('sales', 0);
    }

    /**
     * REVISED: the old version blocked selling a product created by another
     * user. The catalogue is shared store-wide, so this is allowed — the item
     * must simply be active and in stock.
     */
    #[Test]
    public function any_cashier_may_sell_any_active_catalogue_item(): void
    {
        $cashier = $this->makeCashier();
        $product = $this->makeProduct([
            'user_id'      => $this->makeAdmin()->id,
            'price'        => 100.00,
            'current_stock'=> 10,
            'is_active'    => true,
        ]);

        $this->actingAs($cashier)->postJson('/api/pos/checkout', [
            'cart_items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_amount' => 100.00,
        ])->assertOk();

        $this->assertSame(9, (int) $product->fresh()->current_stock);
    }

    /**
     * BRD (Inventory): a deactivated (discontinued) item must not be sellable.
     */
    #[Test]
    public function deactivated_product_cannot_be_sold(): void
    {
        $cashier = $this->makeCashier();
        $product = $this->makeProduct(['price' => 100.00, 'current_stock' => 10, 'is_active' => false]);

        $this->actingAs($cashier)->postJson('/api/pos/checkout', [
            'cart_items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_amount' => 100.00,
        ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'This item is no longer available.');

        $this->assertDatabaseCount('sales', 0);
    }
}
