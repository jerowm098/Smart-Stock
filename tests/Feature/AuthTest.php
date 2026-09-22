<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * SS-61: QA tests for authentication flows.
 *
 * Covers:
 *  - Valid login (web + API)
 *  - Invalid password / wrong credentials
 *  - Logout and session clearing (web + API)
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Web login tests
    // -------------------------------------------------------------------------

    #[Test]
    public function login_page_is_accessible_to_guests(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Sign In');
    }

    #[Test]
    public function valid_credentials_redirect_to_home(): void
    {
        $user = User::factory()->create([
            'email'    => 'admin@example.com',
            'password' => Hash::make('secret123'),
            'role'     => 'admin',
        ]);

        $response = $this->post('/login', [
            'email'    => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function login_ignores_a_stale_dashboard_intended_url(): void
    {
        $user = User::factory()->create([
            'email'    => 'stale@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this
            ->from('/dashboard')
            ->post('/login', [
                'email'    => 'stale@example.com',
                'password' => 'secret123',
            ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function home_page_is_accessible_after_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/home')->assertStatus(200)->assertSee('Smart-Stock');

        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->actingAs($cashier)->get('/home')->assertStatus(200)->assertSee('Smart-Stock');
    }

    #[Test]
    public function home_page_is_accessible_to_guests(): void
    {
        // /home is now public — guests see it with a Login button
        $this->get('/home')->assertStatus(200)->assertSee('Login');
    }

    #[Test]
    public function invalid_password_shows_error_and_does_not_authenticate(): void
    {
        User::factory()->create([
            'email'    => 'cashier@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->post('/login', [
            'email'    => 'cashier@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function empty_email_fails_server_validation(): void
    {
        $response = $this->post('/login', [
            'email'    => '',
            'password' => 'somepassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function empty_password_fails_server_validation(): void
    {
        $response = $this->post('/login', [
            'email'    => 'user@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    #[Test]
    public function nonexistent_email_fails_login(): void
    {
        $response = $this->post('/login', [
            'email'    => 'nobody@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function logout_clears_session_and_redirects_to_home(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/home');
        $this->assertGuest();
    }

    #[Test]
    public function authenticated_user_cannot_access_login_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/home');
    }

    #[Test]
    public function authenticated_user_cannot_access_registration_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/register');

        $response->assertRedirect('/home');
    }

    #[Test]
    public function remember_me_checkbox_persists_session_token_on_login(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('pass1234'),
        ]);

        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'pass1234',
            'remember' => '1',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);

        // Laravel sets a remember_token on the user record when remember=true
        $user->refresh();
        $this->assertNotNull($user->remember_token, 'remember_token should be set when Remember Me is checked');
    }

    #[Test]
    public function login_without_remember_me_does_not_persist_token(): void
    {
        $user = User::factory()->create([
            'password'       => Hash::make('pass1234'),
            'remember_token' => null,
        ]);

        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'pass1234',
            // no 'remember' field
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);

        // Without remember=true, Laravel does NOT write a remember_token
        $user->refresh();
        $this->assertNull($user->remember_token, 'remember_token should remain null when Remember Me is unchecked');
    }

    #[Test]
    public function authenticated_homepage_does_not_show_a_direct_dashboard_shortcut(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/home');

        $response->assertOk();
        $response->assertDontSee('Go to Dashboard');
        $response->assertSee('Dashboard', false);
    }

    // -------------------------------------------------------------------------
    // API auth endpoint tests (SS-59)
    // -------------------------------------------------------------------------

    #[Test]
    public function api_login_returns_json_with_user_data_on_valid_credentials(): void
    {
        User::factory()->create([
            'email'    => 'api@example.com',
            'password' => Hash::make('apipass1'),
            'role'     => 'cashier',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'api@example.com',
            'password' => 'apipass1',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'user' => ['id', 'name', 'email', 'role'],
                 ])
                 ->assertJsonPath('user.email', 'api@example.com')
                 ->assertJsonPath('user.role', 'cashier');
    }

    #[Test]
    public function api_login_returns_401_on_invalid_credentials(): void
    {
        User::factory()->create([
            'email'    => 'api@example.com',
            'password' => Hash::make('correctpass'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'api@example.com',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(401)
                 ->assertJsonPath('message', 'The provided credentials do not match our records.');
    }

    #[Test]
    public function registration_form_contains_role_selection(): void
    {
        $response = $this->get('/register');

        $response->assertOk()
                 ->assertSee('value="admin"', false)
                 ->assertSee('value="cashier"', false);
    }

    #[Test]
    public function user_can_register_with_a_role(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Hardware',
            'last_name' => 'Cashier',
            'username' => 'cashier_reg',
            'email' => 'cashier-registration@example.com',
            'role' => 'cashier',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', [
            'email' => 'cashier-registration@example.com',
            'username' => 'cashier_reg',
            'role' => 'cashier',
            'name' => 'Hardware Cashier',
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'cashier-registration@example.com',
            'password' => 'securepass123',
        ]);
    }

    #[Test]
    public function registration_rejects_an_invalid_role(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Invalid',
            'last_name' => 'User',
            'username' => 'invalid_role_user',
            'email' => 'invalid-role@example.com',
            'role' => 'manager',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', [
            'email' => 'invalid-role@example.com',
        ]);
    }

    #[Test]
    public function registration_rejects_a_duplicate_username(): void
    {
        User::factory()->create([
            'username' => 'taken_user',
            'email' => 'taken-user@example.com',
        ]);

        $response = $this->post('/register', [
            'first_name' => 'Duplicate',
            'last_name' => 'Username',
            'username' => 'taken_user',
            'email' => 'duplicate-username@example.com',
            'role' => 'cashier',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertDatabaseMissing('users', [
            'email' => 'duplicate-username@example.com',
        ]);
    }

    #[Test]
    public function registration_requires_a_username(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Missing',
            'last_name' => 'Username',
            'email' => 'missing-username@example.com',
            'role' => 'cashier',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertDatabaseMissing('users', [
            'email' => 'missing-username@example.com',
        ]);
    }

    #[Test]
    public function registration_requires_first_and_last_name(): void
    {
        $response = $this->post('/register', [
            'email' => 'no-names@example.com',
            'username' => 'no_names_user',
            'role' => 'cashier',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
        ]);

        $response->assertSessionHasErrors(['first_name', 'last_name']);
        $this->assertDatabaseMissing('users', [
            'email' => 'no-names@example.com',
        ]);
    }

    #[Test]
    public function user_can_register_as_admin(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Store',
            'last_name' => 'Admin',
            'username' => 'store_admin',
            'email' => 'admin-registration@example.com',
            'role' => 'admin',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', [
            'email' => 'admin-registration@example.com',
            'username' => 'store_admin',
            'role' => 'admin',
            'name' => 'Store Admin',
        ]);
    }

    #[Test]
    public function registration_rejects_a_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
            'username' => 'existing_user',
        ]);

        $response = $this->followingRedirects()
            ->from('/register')
            ->post('/register', [
                'first_name' => 'Duplicate',
                'last_name' => 'Email',
                'username' => 'new_user',
                'email' => 'existing@example.com',
                'role' => 'cashier',
                'password' => 'securepass123',
                'password_confirmation' => 'securepass123',
            ]);

        // TC-03: block the duplicate email and show a clear error message.
        $response->assertOk();
        $response->assertSee('The email has already been taken.');
        $this->assertDatabaseMissing('users', [
            'username' => 'new_user',
        ]);
    }

    #[Test]
    public function registration_page_is_accessible_to_guests(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
        $this->assertGuest();
    }

    #[Test]
    public function api_user_registration_creates_an_account(): void
    {
        $response = $this->postJson('/api/users/register', [
            'first_name' => 'API',
            'last_name' => 'Cashier',
            'username' => 'api_cashier',
            'email' => 'api-cashier@example.com',
            'role' => 'cashier',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
        ]);

        $response->assertCreated()
                 ->assertJsonPath('message', 'Account created successfully.')
                 ->assertJsonPath('user.email', 'api-cashier@example.com')
                 ->assertJsonPath('user.role', 'cashier');

        $this->assertDatabaseHas('users', [
            'email' => 'api-cashier@example.com',
            'username' => 'api_cashier',
            'role' => 'cashier',
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'api-cashier@example.com',
            'password' => 'securepass123',
        ]);
    }

    #[Test]
    public function api_user_registration_rejects_a_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'api-existing@example.com',
            'username' => 'api_existing_user',
        ]);

        $response = $this->postJson('/api/users/register', [
            'first_name' => 'Duplicate',
            'last_name' => 'Email',
            'username' => 'api_new_user',
            'email' => 'api-existing@example.com',
            'role' => 'cashier',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
        ]);

        $response->assertUnprocessable()
                 ->assertJsonValidationErrors('email');
        $this->assertDatabaseMissing('users', [
            'username' => 'api_new_user',
        ]);
    }

    #[Test]
    public function api_user_registration_rejects_a_duplicate_username(): void
    {
        User::factory()->create([
            'email' => 'api-taken-user@example.com',
            'username' => 'api_taken_user',
        ]);

        $response = $this->postJson('/api/users/register', [
            'first_name' => 'Duplicate',
            'last_name' => 'Username',
            'username' => 'api_taken_user',
            'email' => 'api-duplicate-username@example.com',
            'role' => 'cashier',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
        ]);

        $response->assertUnprocessable()
                 ->assertJsonValidationErrors('username');
        $this->assertDatabaseMissing('users', [
            'email' => 'api-duplicate-username@example.com',
        ]);
    }

    #[Test]
    public function api_user_registration_rejects_missing_fields(): void
    {
        $response = $this->postJson('/api/users/register', []);

        $response->assertUnprocessable()
                 ->assertJsonValidationErrors([
                     'first_name',
                     'last_name',
                     'username',
                     'email',
                     'role',
                     'password',
                 ]);
    }

    #[Test]
    public function api_login_returns_422_on_missing_fields(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email', 'password']);
    }

    #[Test]
    public function api_logout_returns_json_and_clears_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/auth/logout');

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertGuest();
    }

    #[Test]
    public function api_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/logout');

        // Unauthenticated request should be redirected or return 401/302
        $this->assertTrue(
            in_array($response->status(), [302, 401]),
            "Expected 302 or 401, got {$response->status()}"
        );
    }
}
