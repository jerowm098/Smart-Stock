<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InputController extends Controller
{
    /**
     * Show the inputs form page.
     */
    public function create(): View
    {
        return view('inputs');
    }

    /**
     * List all submitted inputs from Supabase.
     */
    public function index(SupabaseService $supabase): View
    {
        try {
            $rows = $supabase->list('inputs') ?: [];
        } catch (\Throwable $e) {
            logger()->error('Failed to fetch inputs from Supabase', ['error' => $e->getMessage()]);
            $rows = [];
        }

        return view('info', compact('rows'));
    }

    /**
     * Store the submitted inputs into Supabase.
     */
    public function store(Request $request, SupabaseService $supabase): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'age' => ['required', 'integer', 'min:1', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
        ]);

        try {
            $supabase->insert('inputs', [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'age' => $validated['age'],
                'address' => $validated['address'],
            ]);

            return back()->with('success', 'Data saved to Supabase successfully!');
        } catch (\Throwable $e) {
            logger()->error('Failed to save input to Supabase', ['error' => $e->getMessage()]);

            return back()
                ->withInput()
                ->with('error', 'Failed to save data to Supabase. Check your SUPABASE_URL and SUPABASE_ANON_KEY in .env.');
        }
    }
}