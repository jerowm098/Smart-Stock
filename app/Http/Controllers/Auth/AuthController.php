<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Sign-in surface.
     *
     * There is no standalone login page any more: the homepage presents the
     * form as a modal (auth/login-card.blade.php, shared with the guest
     * landing page), so guests never leave /home to sign in. This route stays
     * as a redirect rather than being deleted so that an existing bookmark or
     * a hand-typed URL lands somewhere useful instead of a 404.
     *
     * Authenticated users are routed by role, as before.
     */
    public function showLogin(): RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectForRole(Auth::user());
        }

        return redirect()->route('home');
    }

    /**
     * Handle login attempt.
     *
     * BRD (Account Management): "The system shall require a registered user to
     * sign in using their assigned username and password."
     *
     * On success the user is routed by role:
     *   - Staff  -> the simplified POS sales interface
     *   - Admin  -> the main dashboard (inventory and order suggestions)
     */
    public function login(Request $request): RedirectResponse
    {
        // BRD: sign-in uses the assigned username. The `email` key is accepted
        // as a fallback so the store owner can type either identifier into the
        // same field; the error bag is always keyed to `username` because that
        // is the only field the login form renders.
        $request->merge([
            'username' => $request->input('username') ?: $request->input('email'),
        ]);

        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $user = $this->findAuthenticatable($validated['username']);

        // BRD: deactivated accounts are blocked, not deleted.
        if (! $user || ! $user->isActive() || ! Hash::check($validated['password'], $user->password)) {
            return back()
                ->withErrors(['username' => 'The provided credentials do not match our records.'])
                ->onlyInput('username');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return $this->redirectForRole($user);
    }

    /**
     * Route a freshly authenticated user to the surface their role allows.
     *
     * BRD: "The system shall automatically redirect Staff accounts strictly to the
     * simplified POS sales interface upon successful login." Admins go to the
     * dashboard instead.
     */
    private function redirectForRole(User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('pos');
    }

    /**
     * Resolve the account for a login identifier.
     *
     * Accounts carry both a username and an email; the store owner may type
     * either, so both are accepted here while the BRD's username remains the
     * primary identifier.
     */
    private function findAuthenticatable(string $identifier): ?User
    {
        return User::query()
            ->where('username', $identifier)
            ->orWhere('email', $identifier)
            ->first();
    }

    /**
     * BRD (Account Management) removes self-service registration, so the
     * public showRegister()/register() methods were deleted along with the
     * /register routes. Accounts are created by an Admin via
     * App\Http\Controllers\UserController (POST /api/users).
     */

    /**
     * Handle logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * SS-59: Handle API login — returns JSON.
     *
     * Mirrors the web login: username-first identifier (email accepted as a
     * convenience), deactivated accounts rejected, and the response reports the
     * role so the client can route to the POS or the dashboard.
     */
    public function apiLogin(Request $request): \Illuminate\Http\JsonResponse
    {
        // Mirror the web login: username is primary, email accepted as fallback.
        $request->merge([
            'username' => $request->input('username') ?: $request->input('email'),
        ]);

        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $user = $this->findAuthenticatable($validated['username']);

        if (! $user || ! $user->isActive() || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login successful.',
            'redirect' => $user->isAdmin() ? route('dashboard') : route('pos'),
            'user'    => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'email'    => $user->email,
                'role'     => $user->role,
            ],
        ]);
    }

    /**
     * SS-59: Handle API logout — returns JSON and invalidates session.
     */
    public function apiLogout(Request $request): \Illuminate\Http\JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
