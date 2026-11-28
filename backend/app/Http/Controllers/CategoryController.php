<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Hardware Category CRUD API (SS-123 / SS-140).
 */
class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $categories = Category::query()
            ->select('categories.*')
            ->selectSub(
                Product::query()
                    ->selectRaw('COUNT(*)')
                    ->where(function ($q) {
                        $q->whereColumn('products.category_id', 'categories.id')
                            ->orWhereRaw('LOWER(TRIM(products.category)) = LOWER(TRIM(categories.name))');
                    }),
                'products_count'
            )
            ->orderBy('name')
            ->get()
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'description' => $c->description,
                'products_count' => (int) $c->products_count,
                'created_at' => optional($c->created_at)->toISOString(),
                'updated_at' => optional($c->updated_at)->toISOString(),
            ]);

        return response()->json(['categories' => $categories]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $category = Category::create([
            'name' => trim($validated['name']),
            'description' => isset($validated['description']) && trim($validated['description']) !== ''
                ? trim($validated['description'])
                : null,
        ]);

        ActivityLogger::safe(fn () => ActivityLogger::categoryCreated($user, $category));

        return response()->json([
            'message' => 'Category added successfully.',
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'products_count' => 0,
                'created_at' => optional($category->created_at)->toISOString(),
                'updated_at' => optional($category->updated_at)->toISOString(),
            ],
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $category = Category::find($id);
        if (! $category) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $before = $category->only(['name', 'description']);
        $newName = trim($validated['name']);
        $newDesc = isset($validated['description']) && trim($validated['description']) !== ''
            ? trim($validated['description'])
            : null;

        DB::transaction(function () use ($category, $before, $newName, $newDesc) {
            $category->update([
                'name' => $newName,
                'description' => $newDesc,
            ]);

            if ($before['name'] !== $newName) {
                Product::where('category_id', $category->id)
                    ->orWhere('category', $before['name'])
                    ->update([
                        'category' => $newName,
                        'category_id' => $category->id,
                    ]);
            }
        });

        $fresh = Category::withCount('products')->find($category->id);
        ActivityLogger::safe(fn () => ActivityLogger::categoryUpdated($user, $fresh, $before));

        return response()->json([
            'message' => 'Category updated successfully.',
            'category' => [
                'id' => $fresh->id,
                'name' => $fresh->name,
                'description' => $fresh->description,
                'products_count' => (int) $fresh->products_count,
                'created_at' => optional($fresh->created_at)->toISOString(),
                'updated_at' => optional($fresh->updated_at)->toISOString(),
            ],
        ]);
    }

    /**
     * Delete a category — blocked (422) when assigned to any product (SS-142).
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $category = Category::find($id);
        if (! $category) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        $inUseCount = Product::where('category_id', $category->id)
            ->orWhere('category', $category->name)
            ->count();

        if ($inUseCount > 0) {
            return response()->json([
                'message' => "Cannot delete \"{$category->name}\" because {$inUseCount} product(s) are assigned to it.",
            ], 422);
        }

        $name = $category->name;
        $categoryId = $category->id;
        $category->delete();

        ActivityLogger::safe(fn () => ActivityLogger::categoryDeleted($user, $name, $categoryId));

        return response()->json(['message' => 'Category deleted successfully.']);
    }

    public function seed(): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $samples = [
            ['name' => 'Hand Tools', 'description' => 'Manual carpentry and workshop hand tools.'],
            ['name' => 'Building Materials', 'description' => 'Cement, lumber, aggregates, and structural supplies.'],
            ['name' => 'Paints & Coatings', 'description' => 'Interior and exterior paints, primers, rollers, and brushes.'],
            ['name' => 'Plumbing', 'description' => 'PVC pipes, fittings, valves, and sealants.'],
            ['name' => 'Electrical', 'description' => 'Wires, conduits, switches, breakers, and lighting.'],
            ['name' => 'Fasteners', 'description' => 'Nails, screws, bolts, anchors, and washers.'],
            ['name' => 'Power Tools', 'description' => 'Cordless drills, grinders, saws, and electric equipment.'],
        ];

        $created = 0;
        foreach ($samples as $sample) {
            $base = $sample['name'];
            $name = $base;
            $suffix = 2;
            while (Category::where('name', $name)->exists()) {
                $name = "{$base} ({$suffix})";
                $suffix++;
            }

            Category::create([
                'name' => $name,
                'description' => $sample['description'],
            ]);
            $created++;
        }

        return response()->json(['message' => "{$created} sample categories added."], 201);
    }

    public function destroyAll(): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $categories = Category::all();
        if ($categories->isEmpty()) {
            return response()->json(['message' => 'No categories to delete.']);
        }

        $deleted = 0;
        $skipped = 0;
        foreach ($categories as $category) {
            $inUse = Product::where('category_id', $category->id)
                ->orWhere('category', $category->name)
                ->exists();
            if ($inUse) {
                $skipped++;
                continue;
            }
            $category->delete();
            $deleted++;
        }

        if ($deleted === 0 && $skipped > 0) {
            return response()->json([
                'message' => "Could not delete {$skipped} category(ies) because products are assigned to them.",
            ], 422);
        }

        $msg = $skipped > 0
            ? "Deleted {$deleted} category(ies) ({$skipped} kept because products are assigned to them)."
            : "All categories deleted ({$deleted}).";

        return response()->json(['message' => $msg]);
    }
}
