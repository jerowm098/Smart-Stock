<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

        // Security hardening: laging mag-bcrypt check kahit walang user, para
        // pareho ang response time ng "walang ganitong username" at "maling
        // password" (iwas username enumeration sa pamamagitan ng timing).
        $passwordOk = Hash::check(
            $validated['password'],
            $user?->password ?? '$2y$12$pYR/XLCnZWxT4j7B.T6xTOkgzIzzkTC3MmqmkosY1gYk50ayejHiK'
        );

        if (! $user || ! $user->isActive() || ! $passwordOk) {
            return response()->json([
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login successful.',
            // SPA landing path per role. Admin -> Stock Inventory (the first
            // sidebar page, daily entry point), cashier -> POS. Mirrors the
            // SPA homeFor() helper.
            'redirect' => $user->isAdmin() ? '/dashboard' : '/pos',
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

    /**
     * SS-120 / SS-126: Request a password reset token.
     *
     * Stores a SHA-256 hash of the token plus a 30-minute expiry on the user
     * record (SS-128). Because Smart-Stock runs without an external SMTP relay,
     * the reset link/token is returned in the JSON payload when an active
     * account matches so the user can proceed directly to /reset-password.
     */
    public function apiForgotPassword(Request $request): JsonResponse
    {
        $request->merge([
            'identifier' => $request->input('identifier')
                ?: $request->input('email')
                ?: $request->input('username'),
        ]);

        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $user = $this->findAuthenticatable(trim($validated['identifier']));

        if (! $user || ! $user->isActive()) {
            return response()->json([
                'message' => 'If an active account matches that username or email, a password reset link has been generated.',
            ]);
        }

        $plainToken = Str::random(64);
        $expiresAt = now()->addMinutes(30);

        $user->forceFill([
            'reset_token' => hash('sha256', $plainToken),
            'reset_token_expires_at' => $expiresAt,
        ])->save();

        return response()->json([
            'message' => 'Password reset link generated. It will expire in 30 minutes.',
            'reset_token' => $plainToken,
            'reset_url' => "/reset-password?token={$plainToken}",
            'expires_at' => $expiresAt->toISOString(),
        ]);
    }

    /**
     * SS-120 / SS-127: Complete a password reset using a valid, non-expired token.
     */
    public function apiResetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $hashedToken = hash('sha256', trim($validated['token']));
        $user = User::where('reset_token', $hashedToken)->first();

        if (! $user || ! $user->isActive()) {
            return response()->json([
                'message' => 'This password reset token is invalid.',
            ], 422);
        }

        if (! $user->reset_token_expires_at || $user->reset_token_expires_at->isPast()) {
            $user->forceFill([
                'reset_token' => null,
                'reset_token_expires_at' => null,
            ])->save();

            return response()->json([
                'message' => 'This password reset token has expired. Please request a new one.',
            ], 422);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'reset_token' => null,
            'reset_token_expires_at' => null,
        ])->save();

        return response()->json([
            'message' => 'Your password has been reset. You can now sign in with your new password.',
        ]);
    }
}
