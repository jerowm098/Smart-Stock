<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * BRD (Account Management) — Admin-only user management.
 *
 * Functional Requirements implemented here:
 *   - "The system shall allow an Admin to create new accounts with a designated
 *      username, password, and role."
 *   - "The system shall allow an Admin to deactivate a Staff account instead of
 *      deleting it."
 *   - "Changing/resetting passwords manually by Admin."
 *
 * Out of Scope (enforced): self-service registration. There is no public
 * register route; every account is provisioned from here.
 */
class UserController extends Controller
{
    /** Roles assignable by an Admin. 'cashier' is the BRD's "Staff" role. */
    private const ROLES = ['admin', 'cashier'];

    /**
     * List all accounts (active and deactivated) for the Admin table.
     */
    public function list(): JsonResponse
    {
        return response()->json($this->listPayload());
    }

    /**
     * Create a new account with a designated username, password and role.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateCreate($request);

        $user = User::create([
            'name'     => $data['name'],
            'username' => $data['username'],
            'email'    => $data['email'],
            // BRD Security: "Passwords shall be stored using hashing, never in
            // plain text or a reversible form." The model casts password to
            // 'hashed', but we hash explicitly for clarity.
            'password' => Hash::make($data['password']),
            'role'     => $data['role'],
            'is_active'=> true,
        ]);

        ActivityLogger::safe(fn () => ActivityLogger::userCreated($request->user(), $user));

        return response()->json([
            'message' => 'Account created successfully.',
            'user'    => $this->present($user),
        ], 201);
    }

    /**
     * Update an account's name, email, username and/or role.
     * Password changes go through resetPassword() so the intent is explicit.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        if (! $user->is_active) {
            return response()->json([
                'message' => 'Reactivate the account first before changing its details.',
            ], 422);
        }

        $data = $request->validate([
            'name'     => ['sometimes', 'required', 'string', 'max:100', 'regex:/^[A-Za-zÑñ]+(?: [A-Za-zÑñ]+)*$/'],
            'username' => ['sometimes', 'required', 'string', 'min:3', 'max:255', 'regex:/^[A-Za-z0-9]+$/', Rule::unique('users', 'username')->ignore($user->id)],
            'email'    => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'     => ['sometimes', 'required', Rule::in(self::ROLES)],
        ], [
            'name.regex' => 'Full name may only contain letters and spaces.',
            'username.regex' => 'Username may only contain letters and numbers, no special characters.',
        ]);

        // Guard against an Admin demoting themselves out of the admin tier and
        // locking the store out of user management.
        if ($user->is($request->user()) && isset($data['role']) && $data['role'] !== 'admin') {
            throw ValidationException::withMessages([
                'role' => 'You cannot change your own role.',
            ]);
        }

        $before = $user->only(['name', 'username', 'email', 'role']);

        $user->fill($data)->save();

        ActivityLogger::safe(fn () => ActivityLogger::userUpdated($request->user(), $user, $before));

        return response()->json([
            'message' => 'Account updated successfully.',
            'user'    => $this->present($user->fresh()),
        ]);
    }

    /**
     * Deactivate (never delete) an account so sales history is preserved.
     */
    public function deactivate(Request $request, User $user): JsonResponse
    {
        if ($user->is($request->user())) {
            return response()->json([
                'message' => 'You cannot deactivate your own account.',
            ], 422);
        }

        $user->update(['is_active' => false]);

        ActivityLogger::safe(fn () => ActivityLogger::userDeactivated($request->user(), $user));

        return response()->json([
            'message' => 'Account deactivated. Historical transactions remain linked to this user.',
            'user'    => $this->present($user->fresh()),
        ]);
    }

    /**
     * Reactivate a previously deactivated account.
     */
    public function activate(Request $request, User $user): JsonResponse
    {
        $user->update(['is_active' => true]);

        ActivityLogger::safe(fn () => ActivityLogger::userReactivated($request->user(), $user));

        return response()->json([
            'message' => 'Account reactivated.',
            'user'    => $this->present($user->fresh()),
        ]);
    }

    /**
     * Manually reset a user's password (Admin only, no email involved).
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        if (! $user->is_active) {
            return response()->json([
                'message' => 'Reactivate the account first before resetting its password.',
            ], 422);
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        ActivityLogger::safe(fn () => ActivityLogger::userPasswordReset($request->user(), $user));

        return response()->json([
            'message' => 'Password reset successfully.',
        ]);
    }

    /**
     * Add 7 sample user accounts (demo/testing data, mirrors BackupController::seed).
     */
    public function seed(Request $request): JsonResponse
    {
        $samples = [
            ['name' => 'Maria Santos', 'prefix' => 'msantos', 'role' => 'cashier'],
            ['name' => 'Juan Dela Cruz', 'prefix' => 'jdelacruz', 'role' => 'cashier'],
            ['name' => 'Ana Reyes', 'prefix' => 'areyes', 'role' => 'cashier'],
            ['name' => 'Carlo Mendoza', 'prefix' => 'cmendoza', 'role' => 'cashier'],
            ['name' => 'Liza Bautista', 'prefix' => 'lbautista', 'role' => 'cashier'],
            ['name' => 'Mark Villanueva', 'prefix' => 'mvillanueva', 'role' => 'admin'],
            ['name' => 'Grace Navarro', 'prefix' => 'gnavarro', 'role' => 'admin'],
        ];

        $hash = Hash::make('123456');
        $created = 0;

        foreach ($samples as $sample) {
            $suffix = 1;
            do {
                $username = $sample['prefix'] . ($suffix === 1 ? '' : $suffix);
                $email = $username . '@smartstock.local';
                $exists = User::where('username', $username)->orWhere('email', $email)->exists();
                $suffix++;
            } while ($exists);

            User::create([
                'name' => $sample['name'],
                'username' => $username,
                'email' => $email,
                'password' => $hash,
                'role' => $sample['role'],
                'is_active' => true,
            ]);
            $created++;
        }

        return response()->json([
            'message' => "{$created} sample users added.",
        ], 201);
    }

    /**
     * Delete all other user accounts (never deletes the currently signed-in admin,
     * and gracefully preserves any user protected by transaction FK constraints).
     */
    public function destroyAll(Request $request): JsonResponse
    {
        $me = $request->user();
        $others = User::where('id', '!=', $me->id)->get();

        if ($others->isEmpty()) {
            return response()->json(['message' => 'No other users to delete.']);
        }

        $deleted = 0;
        $skipped = 0;

        foreach ($others as $other) {
            try {
                $other->delete();
                $deleted++;
            } catch (\Illuminate\Database\QueryException) {
                $skipped++;
            }
        }

        if ($deleted === 0 && $skipped > 0) {
            return response()->json([
                'message' => "Could not delete {$skipped} user(s) because they have linked transaction history.",
            ], 422);
        }

        $msg = $skipped > 0
            ? "Deleted {$deleted} user(s) ({$skipped} kept due to linked transaction history)."
            : "All other users deleted ({$deleted}).";

        return response()->json(['message' => $msg]);
    }

    /**
     * Shared validation for account creation.
     */
    private function validateCreate(Request $request): array
    {
        return $request->validate([
            'name'     => ['required', 'string', 'max:100', 'regex:/^[A-Za-zÑñ]+(?: [A-Za-zÑñ]+)*$/'],
            'username' => ['required', 'string', 'min:3', 'max:255', 'regex:/^[A-Za-z0-9]+$/', Rule::unique('users', 'username')],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role'     => ['required', Rule::in(self::ROLES)],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'name.regex' => 'Full name may only contain letters and spaces.',
            'username.regex' => 'Username may only contain letters and numbers, no special characters.',
        ]);
    }

    /**
     * Build the list payload including counts and role options.
     */
    private function listPayload(): array
    {
        // Recently added-or-updated accounts float to the top. updated_at is
        // bumped by account creation, role changes, (de)activation and
        // password resets, so the Admin can trace where a change landed.
        $users = User::orderByDesc('updated_at')
            ->get()
            ->map(fn (User $u) => $this->present($u));

        return [
            'users' => $users,
            'roles' => self::ROLES,
        ];
    }

    /**
     * Shape a user for the API (never leak the password hash).
     */
    private function present(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'username'   => $user->username,
            'email'      => $user->email,
            'role'       => $user->role,
            'is_active'  => (bool) $user->is_active,
            'created_at' => optional($user->created_at)->diffForHumans(),
            'updated_at' => optional($user->updated_at)->diffForHumans(),
            // Machine-readable timestamp so the table can sort by "Updated"
            // (the relative text above is for display only).
            'updated_at_iso' => optional($user->updated_at)->toIso8601String(),
        ];
    }
}
