<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
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
     * Who is signed in? Used by the React SPA on boot.
     *
     * Public on purpose: the SPA asks this before it knows whether a session
     * exists, and the response also carries the XSRF-TOKEN cookie the SPA
     * needs before its first POST.
     */
    public function apiUser(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $user ? [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'email'    => $user->email,
                'role'     => $user->role,
            ] : null,
        ]);
    }

    /**
     * SS-59: Handle API login — returns JSON.
     *
     * Username-first identifier (email accepted as a convenience),
     * deactivated accounts rejected, and the response reports the role plus
     * the SPA landing path so the client can route to the POS or dashboard.
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
            // SPA landing path per role (admin -> Stock-In, cashier -> POS).
            'redirect' => $user->isAdmin() ? '/stock-in' : '/pos',
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
