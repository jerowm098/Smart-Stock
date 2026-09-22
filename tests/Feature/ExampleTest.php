<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/home');
    }

    public function test_authenticated_user_can_access_dashboard_and_products(): void
    {
        $user = \App\Models\User::factory()->create();

        $responseDashboard = $this->actingAs($user)->get('/dashboard');
        $responseDashboard->assertStatus(200);

        $responseProducts = $this->actingAs($user)->get('/products');
        $responseProducts->assertStatus(200);
    }
}
