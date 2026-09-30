<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithStore;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/home');
    }

    /**
     * REVISED: the default User factory produces a `cashier`. Under the BRD role
     * model ("Staff Role = POS Access Only") a cashier is redirected away from
     * /products, so this test now signs in as an Admin — the role that owns
     * Inventory.
     */
    public function test_authenticated_admin_can_access_dashboard_and_products(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->actingAs($admin)->get('/products')->assertOk();
    }

    /**
     * The BRD counterpart: a Staff account reaches the POS, not Inventory.
     */
    public function test_cashier_reaches_pos_but_is_redirected_from_products(): void
    {
        $cashier = $this->makeCashier();

        $this->actingAs($cashier)->get('/pos')->assertOk();
        $this->actingAs($cashier)->get('/products')->assertRedirect();
    }
}
