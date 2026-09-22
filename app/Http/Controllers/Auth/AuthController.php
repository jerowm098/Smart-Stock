<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Database\Seeders\ProductSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the login page.
     * Redirects already-authenticated users to the homepage.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    /**
     * Handle login attempt.
     * On success, validates active session and redirects to the homepage.
     */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if (Auth::attempt($validated, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->route('home');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Show the register page.
     */
    public function showRegister(): View
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.register');
    }

    /**
     * Handle registration.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'username' => 'required|string|min:3|max:255|alpha_dash|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:admin,cashier',
            'password' => 'required|string|confirmed|min:6',
        ]);

        $user = User::create([
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        // SS-49: Seed the new account with the sample hardware inventory
        // so the catalogue (SS-17) and search/filter features (SS-18)
        // have real data to display and test against from day one.
        app(ProductSeeder::class)->seedForUser($user->id);

        return redirect()->route('login')->with('success', 'Account created! You can now sign in.');
    }

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
     */
    public function apiLogin(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if (Auth::attempt($validated, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return response()->json([
                'message' => 'Login successful.',
                'user'    => [
                    'id'    => Auth::id(),
                    'name'  => Auth::user()->name,
                    'email' => Auth::user()->email,
                    'role'  => Auth::user()->role,
                ],
            ]);
        }

        return response()->json([
            'message' => 'The provided credentials do not match our records.',
        ], 401);
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
     * SS-46: Handle API registration — validates fields and rejects
     * duplicate email/username before creating the account.
     *
     * Returns JSON on success (201) and JSON validation errors (422)
     * on duplicate email/username or invalid input.
     */
    public function apiRegister(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'username'  => 'required|string|min:3|max:255|alpha_dash|unique:users,username',
            'email'     => 'required|email|max:255|unique:users,email',
            'role'      => 'required|in:admin,cashier',
            'password'  => 'required|string|confirmed|min:6',
        ]);

        $user = User::create([
            'name'     => trim($validated['first_name'] . ' ' . $validated['last_name']),
            'username' => $validated['username'],
            'email'    => $validated['email'],
            'role'     => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        // SS-49: Seed the new account with the sample hardware inventory
        // so the catalogue (SS-17) and search/filter features (SS-18)
        // have real data to display and test against from day one.
        app(ProductSeeder::class)->seedForUser($user->id);

        return response()->json([
            'message' => 'Account created successfully.',
            'user'    => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'email'    => $user->email,
                'role'     => $user->role,
            ],
        ], 201);
    }
}
