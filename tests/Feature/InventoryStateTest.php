<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * UI loading / empty states.
 *
 * REVISED: these pages are Admin-only under the BRD role model
 * ("Staff Role = POS Access Only"), so the tests must act as an Admin rather
 * than the default cashier factory user.
 */
class InventoryStateTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithStore;

    #[Test]
    public function products_page_renders_loading_spinner_on_initial_load(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/products')
            ->assertOk()
            ->assertSee('Loading products...')
            ->assertSee('spinner', false);
    }

    #[Test]
    public function products_page_shows_empty_state_when_no_products_exist(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/products')
            ->assertOk()
            ->assertSee('No products found');
    }

    /**
     * NOTE: /dashboard renders resources/views/overview.blade.php for Admins
     * (InventoryController::index), so the assertions target the strings that
     * view actually contains. The "Loading dashboard data..." / "No products
     * yet" rows live in the older dashboard.blade.php, which is no longer the
     * view bound to this route.
     */
    #[Test]
    public function overview_page_renders_loading_spinner_on_initial_load(): void
    {
        $response = $this->actingAs($this->makeAdmin())->get('/dashboard');

        $response->assertOk();
        $response->assertSee('spinner', false);
        $response->assertSee('Order Suggestions');
    }

    #[Test]
    public function overview_page_shows_empty_state_for_no_sales(): void
    {
        $response = $this->actingAs($this->makeAdmin())->get('/dashboard');

        $response->assertOk();
        $response->assertSee('No sales recorded yet');
    }

    /**
     * BRD: "Staff Role = POS Access Only." A Staff session gets the cashier
     * overview variant, never the Admin dashboard markup.
     */
    #[Test]
    public function cashier_sees_the_cashier_overview_not_the_admin_dashboard(): void
    {
        $response = $this->actingAs($this->makeCashier())->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('Order Suggestions');
    }
}
