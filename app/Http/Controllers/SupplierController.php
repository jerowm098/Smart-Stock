<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    /**
     * Show the supplier directory page.
     */
    public function index()
    {
        return view('suppliers');
    }

    /**
     * Get all active suppliers.
     */
    public function getActive(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user) {
            return response()->json([], 401);
        }

        $suppliers = Supplier::where('is_active', true)->latest()->get();

        return response()->json($suppliers);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        $supplier = Supplier::create($validated);

        return response()->json(['message' => 'Supplier added successfully', 'supplier' => $supplier], 201);
    }

    /**
     * Resolve the authenticated user used for ownership checks.
     */
    protected function currentUser(): ?\App\Models\User
    {
        return Auth::user();
    }
}