<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Authentication flows.
 *
 * REVISED (was: AuthTest).
 * BRD (Account Management) changed the sign-in contract:
 *
 *  - "The system shall require a registered user to sign in using their
 *     assigned username and password."  => the login field is `username`,
 *     not `email`.
 *  - "The system shall automatically redirect Staff accounts strictly to the
 *     simplified POS sales interface upon successful login." / "Admins should
 *     be redirected to the main dashboard." => the redirect is ROLE-based,
 *     not always /home.
 *  - "The login screen shall consist only of username, password, and a submit
 *     button with no distracting elements." => the "Remember me" checkbox was
 *     removed, so the remember_token assertions no longer apply.
 *  - Out of Scope: "Self-service account registration" => /register is gone.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithStore;

    // -------------------------------------------------------------------------
    // Web login
    // -------------------------------------------------------------------------

    /**
     * Sign-in is presented as a modal on the homepage, not a standalone page.
     * The route is kept only so old bookmarks land somewhere useful.
     */
    #[Test]
    public function login_route_redirects_guests_to_the_homepage_modal(): void
    {
        $this->get('/login')
            ->assertRedirect(route('home'));

        // The form itself is present on the homepage, inside the modal.
        $this->get('/home')
            ->assertOk()
            ->assertSee('Sign In')
            ->assertSee('name="username"', false);
    }

    /**
     * BRD Usability: the surface holds only username, password and submit.
     */
    #[Test]
    public function login_form_has_no_remember_me_checkout(): void
    {
        $this->get('/home')
            ->assertOk()
            ->assertDontSee('name="remember"', false)
            ->assertDontSee('Remember me');
    }

    /**
     * BRD: "staff cannot create their own accounts" — no public registration.
     */
    #[Test]
    public function registration_page_no_longer_exists(): void
    {
        $this->get('/register')->assertNotFound();
    }

    #[Test]
    public function admin_login_redirects_to_dashboard(): void
    {
        $admin = $this->makeAdmin(['username' => 'owner', 'password' => 'secret123']);

        $this->post('/login', ['username' => 'owner', 'password' => 'secret123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    /**
     * BRD: Staff are redirected "strictly to the simplified POS sales interface".
     */
    #[Test]
    public function cashier_login_redirects_to_pos(): void
    {
        $cashier = $this->makeCashier(['username' => 'cashier', 'password' => 'secret123']);

        $this->post('/login', ['username' => 'cashier', 'password' => 'secret123'])
            ->assertRedirect(route('pos'));

        $this->assertAuthenticatedAs($cashier);
    }

    #[Test]
    public function login_works_with_the_email_identifier_too(): void
    {
        $admin = $this->makeAdmin([
            'username' => 'owner',
            'email'    => 'owner@example.com',
            'password' => 'secret123',
        ]);

        $this->post('/login', ['email' => 'owner@example.com', 'password' => 'secret123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    #[Test]
    public function invalid_password_shows_error_and_does_not_authenticate(): void
    {
        $this->makeCashier(['username' => 'cashier', 'password' => 'correct-password']);

        $this->post('/login', ['username' => 'cashier', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    #[Test]
    public function empty_username_fails_validation(): void
    {
        $this->post('/login', ['username' => '', 'password' => 'somepassword'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    #[Test]
    public function empty_password_fails_validation(): void
    {
        $this->post('/login', ['username' => 'someone', 'password' => ''])
            ->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    #[Test]
    public function unknown_username_fails_login(): void
    {
        $this->post('/login', ['username' => 'nobody', 'password' => 'password123'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    /**
     * BRD: deactivated accounts are blocked but never deleted.
     */
    #[Test]
    public function deactivated_account_cannot_sign_in(): void
    {
        $this->makeCashier([
            'username'  => 'former',
            'password'  => 'secret123',
            'is_active' => false,
        ]);

        $this->post('/login', ['username' => 'former', 'password' => 'secret123'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    #[Test]
    public function logout_clears_session(): void
    {
        $user = $this->makeAdmin();

        $this->actingAs($user)->post('/logout')->assertRedirect();

        $this->assertGuest();
    }

    #[Test]
    public function authenticated_user_is_bounced_from_login_by_role(): void
    {
        $this->actingAs($this->makeAdmin())->get('/login')->assertRedirect(route('dashboard'));
        $this->actingAs($this->makeCashier())->get('/login')->assertRedirect(route('pos'));
    }

    // -------------------------------------------------------------------------
    // Public pages
    // -------------------------------------------------------------------------

    #[Test]
    public function home_page_is_accessible_to_guests(): void
    {
        $this->get('/home')->assertOk()->assertSee('Login');
    }

    #[Test]
    public function home_page_is_accessible_after_login(): void
    {
        $this->actingAs($this->makeAdmin())->get('/home')->assertOk();
        $this->actingAs($this->makeCashier())->get('/home')->assertOk();
    }

    // -------------------------------------------------------------------------
    // API login
    // -------------------------------------------------------------------------

    #[Test]
    public function api_login_returns_json_with_user_data(): void
    {
        $this->makeCashier([
            'username' => 'apiuser',
            'email'    => 'api@example.com',
            'password' => 'apipass1',
        ]);

        $this->postJson('/api/auth/login', ['username' => 'apiuser', 'password' => 'apipass1'])
            ->assertOk()
            ->assertJsonStructure(['message', 'redirect', 'user' => ['id', 'name', 'username', 'email', 'role']])
            ->assertJsonPath('user.username', 'apiuser')
            ->assertJsonPath('user.role', 'cashier');
    }

    #[Test]
    public function api_login_reports_the_role_specific_redirect(): void
    {
        $this->makeCashier(['username' => 'apipos', 'password' => 'apipass1']);
        $this->makeAdmin(['username' => 'apiadmin', 'password' => 'apipass1']);

        $this->postJson('/api/auth/login', ['username' => 'apipos', 'password' => 'apipass1'])
            ->assertJsonPath('redirect', route('pos'));

        $this->post('/logout');

        $this->postJson('/api/auth/login', ['username' => 'apiadmin', 'password' => 'apipass1'])
            ->assertJsonPath('redirect', route('dashboard'));
    }

    #[Test]
    public function api_login_returns_401_on_invalid_credentials(): void
    {
        $this->makeCashier(['username' => 'apiuser', 'password' => 'correctpass']);

        $this->postJson('/api/auth/login', ['username' => 'apiuser', 'password' => 'wrongpass'])
            ->assertStatus(401)
            ->assertJsonPath('message', 'The provided credentials do not match our records.');
    }

    #[Test]
    public function api_login_rejects_deactivated_accounts(): void
    {
        $this->makeCashier([
            'username'  => 'gone',
            'password'  => 'secret123',
            'is_active' => false,
        ]);

        $this->postJson('/api/auth/login', ['username' => 'gone', 'password' => 'secret123'])
            ->assertStatus(401);

        $this->assertGuest();
    }

    #[Test]
    public function api_logout_clears_the_session(): void
    {
        $user = $this->makeAdmin();

        $this->actingAs($user)->postJson('/api/auth/logout')->assertOk();

        $this->assertGuest();
    }

    /**
     * BRD Security: "Passwords shall be stored using hashing, never in plain
     * text or a reversible form."
     */
    #[Test]
    public function stored_passwords_are_hashed(): void
    {
        $user = $this->makeAdmin(['username' => 'hashed', 'password' => 'secret123']);

        $this->assertNotSame('secret123', $user->password);
        $this->assertTrue(Hash::check('secret123', $user->fresh()->password));
    }
}
