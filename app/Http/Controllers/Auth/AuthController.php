<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthUser;
use App\Repositories\AuthRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    protected AuthRepository $authRepo;

    public function __construct(AuthRepository $authRepo)
    {
        $this->authRepo = $authRepo;
    }

    /**
     * Show the login page.
     */
    public function showLogin(): View
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle login attempt.
     */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $user = $this->authRepo->authenticate($validated['email'], $validated['password']);

        if ($user) {
            // Create a proper AuthUser model instance
            $authUser = AuthUser::fromSupabase($user);
            
            Auth::login($authUser, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
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
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    /**
     * Handle registration.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|confirmed|min:6',
        ]);

        try {
            $user = $this->authRepo->register($validated);
            
            // Create a proper AuthUser model instance
            $authUser = AuthUser::fromSupabase($user);
            
            Auth::login($authUser);
            $request->session()->regenerate();

            return redirect()->route('dashboard');
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Email already registered')) {
                return back()->withErrors([
                    'email' => 'This email is already registered.',
                ])->onlyInput('email');
            }
            
            throw $e;
        }
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
