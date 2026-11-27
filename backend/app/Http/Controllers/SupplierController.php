<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    /**
     * Get all active suppliers.
     */
    public function getActive(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user) {
            return response()->json([], 401);
        }

        $suppliers = Supplier::active()->latest('created_at')->get();

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

        ActivityLogger::safe(fn () => ActivityLogger::supplierCreated($user, $supplier));

        return response()->json(['message' => 'Supplier added successfully', 'supplier' => $supplier], 201);
    }

    /**
     * Update an existing supplier record (SS-124 / SS-144).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $supplier = Supplier::find($id);
        if (! $supplier) {
            return response()->json(['message' => 'Supplier not found.'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        $before = $supplier->only(['name', 'contact_person', 'phone', 'email']);
        $supplier->update($validated);

        ActivityLogger::safe(fn () => ActivityLogger::supplierUpdated($user, $supplier->fresh(), $before));

        return response()->json([
            'message' => 'Supplier updated successfully.',
            'supplier' => $supplier->fresh(),
        ]);
    }

    /**
     * Soft-delete (deactivate) a supplier.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $supplier = Supplier::find($id);

        if (! $supplier) {
            return response()->json(['message' => 'Supplier not found.'], 404);
        }

        $supplier->update(['is_active' => false]);

        ActivityLogger::safe(fn () => ActivityLogger::supplierRemoved($user, $supplier));

        return response()->json(['message' => 'Supplier deleted successfully.']);
    }

    /**
     * Seed 7 sample hardware suppliers for quick pagination / table testing.
     */
    public function seed(): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $samples = [
            ['name' => 'Metro Steel & Hardware Supply', 'contact_person' => 'Ramon Villanueva', 'phone' => '0917-482-1104', 'email' => 'sales@metrosteel.example.com'],
            ['name' => 'Pacific Lumber & Builders Co.', 'contact_person' => 'Elena Soriano', 'phone' => '0918-553-2091', 'email' => 'orders@pacificlumber.example.com'],
            ['name' => 'Northern Electrical Distributors', 'contact_person' => 'Marco Bautista', 'phone' => '0927-614-8820', 'email' => 'supply@northernelec.example.com'],
            ['name' => 'Apex Paint & Coatings Corp.', 'contact_person' => 'Clarissa Mendoza', 'phone' => '0919-740-3315', 'email' => 'dispatch@apexpaint.example.com'],
            ['name' => 'Summit Plumbing & Fixtures', 'contact_person' => 'Dante Pascual', 'phone' => '0920-812-4498', 'email' => 'accounts@summitplumbing.example.com'],
            ['name' => 'BuildRight Fasteners Inc.', 'contact_person' => 'Teresita Aquino', 'phone' => '0916-905-6723', 'email' => 'hello@buildright.example.com'],
            ['name' => 'Visayas Cement & Aggregates', 'contact_person' => 'Nestor Castillo', 'phone' => '0915-338-7540', 'email' => 'logistics@visayascement.example.com'],
        ];

        $created = 0;

        foreach ($samples as $sample) {
            $baseName = $sample['name'];
            $name = $baseName;
            $suffix = 2;

            while (Supplier::active()->where('name', $name)->exists()) {
                $name = "{$baseName} ({$suffix})";
                $suffix++;
            }

            Supplier::create([
                'name' => $name,
                'contact_person' => $sample['contact_person'],
                'phone' => $sample['phone'],
                'email' => $sample['email'],
                'is_active' => true,
            ]);
            $created++;
        }

        return response()->json([
            'message' => "{$created} sample suppliers added.",
        ], 201);
    }

    /**
     * Soft-delete (deactivate) all active suppliers.
     */
    public function destroyAll(): JsonResponse
    {
        $user = $this->currentUser();
        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $count = Supplier::active()->count();

        if ($count === 0) {
            return response()->json(['message' => 'No active suppliers to delete.']);
        }

        Supplier::active()->update(['is_active' => false]);

        return response()->json([
            'message' => "All suppliers removed ({$count}).",
        ]);
    }

    /**
     * Resolve the authenticated user used for ownership checks.
     */
    protected function currentUser(): ?\App\Models\User
    {
        return Auth::user();
    }
}