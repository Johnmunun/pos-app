<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display categories
     */
    public function index(): Response
    {
        $tenantId = Auth::user()->tenant_id;

        if ($tenantId === null) {
            return Inertia::render('Categories/Index', [
                'categories' => [],
            ]);
        }

        $categories = Category::where('tenant_id', $tenantId)
            ->with('parent')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'image' => $category->image,
                'parent_id' => $category->parent_id,
                'parent_name' => $category->parent?->name,
                'sort_order' => $category->sort_order,
                'is_active' => $category->is_active,
                'products_count' => $category->products()->count(),
            ]);

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a new category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $tenantId = Auth::user()->tenant_id;

        if ($tenantId === null) {
            return redirect()->back()->with('error', 'Vous devez être associé à un tenant.');
        }

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Ensure unique slug for tenant
        $slug = $validated['slug'];
        $counter = 1;
        while (Category::where('tenant_id', $tenantId)->where('slug', $slug)->exists()) {
            $slug = $validated['slug'] . '-' . $counter;
            $counter++;
        }
        $validated['slug'] = $slug;

        Category::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->back()->with('success', 'Catégorie créée avec succès.');
    }

    /**
     * Update a category
     */
    public function update(Request $request, Category $category)
    {
        $tenantId = Auth::user()->tenant_id;

        if ($category->tenant_id !== $tenantId) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Prevent self-parent
        if ($validated['parent_id'] == $category->id) {
            return redirect()->back()->with('error', 'Une catégorie ne peut pas être son propre parent.');
        }

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Ensure unique slug for tenant
        $slug = $validated['slug'];
        if ($slug !== $category->slug) {
            $counter = 1;
            while (Category::where('tenant_id', $tenantId)->where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = $validated['slug'] . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        $category->update($validated);

        return redirect()->back()->with('success', 'Catégorie mise à jour avec succès.');
    }

    /**
     * Delete a category
     */
    public function destroy(Category $category)
    {
        $tenantId = Auth::user()->tenant_id;

        if ($category->tenant_id !== $tenantId) {
            abort(403);
        }

        // Check if category has products
        if ($category->products()->count() > 0) {
            return redirect()->back()->with('error', 'Impossible de supprimer une catégorie contenant des produits.');
        }

        // Check if category has children
        if ($category->children()->count() > 0) {
            return redirect()->back()->with('error', 'Impossible de supprimer une catégorie contenant des sous-catégories.');
        }

        $category->delete();

        return redirect()->back()->with('success', 'Catégorie supprimée avec succès.');
    }
}

