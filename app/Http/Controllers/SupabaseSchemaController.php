<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;
use Throwable;

class SupabaseSchemaController
{
    public function login(): View
    {
        return view('schema-login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $request->validate([
            'admin_token' => ['required', 'string'],
        ]);

        $expectedToken = (string) config('services.supabase.schema_admin_token');

        if ($expectedToken === '' || ! hash_equals($expectedToken, $request->input('admin_token'))) {
            return back()
                ->withErrors(['admin_token' => 'Invalid administrator token.'])
                ->withInput();
        }

        Cookie::queue(Cookie::make(
            'supabase_schema_admin',
            $expectedToken,
            8 * 60,
            '/',
            null,
            app()->isProduction(),
            true,
            'Lax'
        ));

        return redirect()
            ->route('schema.index')
            ->with('success', 'Administrator session started.');
    }

    public function index(): View
    {
        return view('schema');
    }

    public function update(SupabaseService $supabase): RedirectResponse
    {
        try {
            $supabase->updateDatabase();
        } catch (Throwable $exception) {
            logger()->error('Supabase schema update failed', [
                'error' => $exception->getMessage(),
            ]);

            return back()->with('error', 'The schema could not be updated. Check the server logs for details.');
        }

        return back()->with('success', 'Database schema updated successfully.');
    }

    public function reset(SupabaseService $supabase): RedirectResponse
    {
        try {
            $supabase->resetDatabase();
        } catch (Throwable $exception) {
            logger()->error('Supabase schema reset failed', [
                'error' => $exception->getMessage(),
            ]);

            return back()->with('error', 'The schema could not be reset. Check the server logs for details.');
        }

        return back()->with('success', 'Database schema reset and restored from database/dev.sql.');
    }

    public function logout(): RedirectResponse
    {
        Cookie::queue(Cookie::forget('supabase_schema_admin'));

        return redirect()
            ->route('schema.login')
            ->with('success', 'Administrator session closed.');
    }
}
