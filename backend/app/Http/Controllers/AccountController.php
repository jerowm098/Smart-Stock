<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Self-service account settings for the signed-in user.
 *
 * The Users page manages every OTHER account (Admin only). The account that is
 * currently signed in is edited from the header account menu instead, so this
 * controller is auth-guarded but role-agnostic: an Admin and a Cashier can each
 * update their own name, username, email and password — never their own role.
 *
 * BRD (Account Management) still forbids self-service REGISTRATION; this only
 * lets an existing account maintain its own details.
 */
class AccountController extends Controller
{
    /**
     * Update the authenticated user's own account.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $data = $request->validate([
            'name'     => ['sometimes', 'required', 'string', 'max:100', 'regex:/^[A-Za-zÑñ]+(?: [A-Za-zÑñ]+)*$/'],
            'username' => ['sometimes', 'required', 'string', 'min:3', 'max:255', 'regex:/^[A-Za-z0-9]+$/', Rule::unique('users', 'username')->ignore($user->id)],
            'email'    => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            // Optional: an empty password keeps the current one.
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ], [
            'name.regex' => 'Full name may only contain letters and spaces.',
            'username.regex' => 'Username may only contain letters and numbers, no special characters.',
        ]);

        // The role is intentionally NOT accepted here: nobody can promote or
        // demote themselves from the account modal.
        $before = $user->only(['name', 'username', 'email', 'role']);

        $passwordChanged = ! empty($data['password']);

        $user->fill(array_intersect_key($data, array_flip(['name', 'username', 'email'])));

        if ($passwordChanged) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        ActivityLogger::safe(fn () => ActivityLogger::userUpdated($user, $user->fresh(), $before, $passwordChanged));

        return response()->json([
            'message' => $passwordChanged
                ? 'Account and password updated successfully.'
                : 'Account updated successfully.',
            'user'    => $this->present($user->fresh()),
        ]);
    }

    /**
     * Shape the account for the SPA (never leak the password hash).
     */
    private function present(User $user): array
    {
        return [
            'id'       => $user->id,
            'name'     => $user->name,
            'username' => $user->username,
            'email'    => $user->email,
            'role'     => $user->role,
        ];
    }
}
