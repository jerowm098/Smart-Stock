<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * SS-48: UI tests for loading spinner and empty-state table views.
 *
 * Covers:
 *  - Products page renders the loading spinner while data is fetched.
 *  - Products page shows an empty-state row when no records exist.
 *  - Overview page renders the loading spinner while data is fetched.
 *  - Overview page shows an empty-state row when no records exist.
 */
class InventoryStateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function products_page_renders_loading_spinner_on_initial_load(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/products');

        $response->assertStatus(200);
        $response->assertSee('Loading products...');
        $response->assertSee('spinner');
    }

    #[Test]
    public function products_page_shows_empty_state_when_no_products_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/products');

        $response->assertStatus(200);
        $response->assertSee('No products found');
    }

    #[Test]
    public function overview_page_renders_loading_spinner_on_initial_load(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Loading dashboard data...');
        $response->assertSee('spinner');
    }

    #[Test]
    public function overview_page_shows_empty_state_when_no_products_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('No products yet');
    }

}