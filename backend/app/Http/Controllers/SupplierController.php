<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockIn;
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

        $affectedIds = StockIn::where('supplier_id', $supplier->id)
            ->distinct()
            ->pluck('product_id');

        $supplier->update(['is_active' => false]);

        $deactivatedProducts = 0;
        if ($affectedIds->isNotEmpty()) {
            $deactivatedProducts = Product::whereIn('id', $affectedIds)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        ActivityLogger::safe(fn () => ActivityLogger::supplierRemoved($user, $supplier));

        return response()->json([
            'message' => $deactivatedProducts > 0
                ? "Supplier deleted. {$deactivatedProducts} linked product(s) deactivated."
                : 'Supplier deleted successfully.',
            'affected_products' => $deactivatedProducts,
        ]);
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
            ['name' => 'Metro Hardware Trading', 'contact_person' => 'Ramon Reyes', 'phone' => '0917-555-0101', 'email' => 'sales@metrohardware.ph'],
            ['name' => 'BuildRight Supply Corp.', 'contact_person' => 'Elena Cruz', 'phone' => '0918-555-0102', 'email' => 'orders@buildright.ph'],
            ['name' => 'PrimeCoat & Electrical Co.', 'contact_person' => 'Marco Santos', 'phone' => '0919-555-0103', 'email' => 'dispatch@primecoat.ph'],
            ['name' => 'FlowLine Plumbing Depot', 'contact_person' => 'Liza Mendoza', 'phone' => '0920-555-0104', 'email' => 'supply@flowline.ph'],
            ['name' => 'VoltEdge Electrical Supplies', 'contact_person' => 'Carlos Villanueva', 'phone' => '0921-555-0105', 'email' => 'sales@voltedge.ph'],
            ['name' => 'SteelGrip Fasteners Inc.', 'contact_person' => 'Grace Bautista', 'phone' => '0922-555-0106', 'email' => 'orders@steelgrip.ph'],
            ['name' => 'Apex Power Tools Distribution', 'contact_person' => 'Dennis Aquino', 'phone' => '0923-555-0107', 'email' => 'support@apexpowertools.ph'],
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

        $activeIds = Supplier::active()->pluck('id');
        $count = $activeIds->count();

        if ($count === 0) {
            return response()->json(['message' => 'No active suppliers to delete.', 'affected_products' => 0]);
        }

        $affectedIds = StockIn::whereIn('supplier_id', $activeIds)
            ->distinct()
            ->pluck('product_id');

        Supplier::whereIn('id', $activeIds)->update(['is_active' => false]);

        $deactivatedProducts = 0;
        if ($affectedIds->isNotEmpty()) {
            $deactivatedProducts = Product::whereIn('id', $affectedIds)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        return response()->json([
            'message' => $deactivatedProducts > 0
                ? "All suppliers removed ({$count}). {$deactivatedProducts} linked product(s) deactivated."
                : "All suppliers removed ({$count}).",
            'affected_products' => $deactivatedProducts,
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