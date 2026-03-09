<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Ecommerce\Models\CmsBlogCategoryModel;

class CmsBlogCategoryController
{
    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if (!$user) abort(403, 'User not authenticated.');
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        $isRoot = \App\Models\User::find($user->id)?->isRoot() ?? false;
        if (!$shopId && !$isRoot) abort(403, 'Shop ID not found.');
        if ($isRoot && !$shopId) abort(403, 'Please select a shop first.');
        return (string) $shopId;
    }

    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);

        if (!\Illuminate\Support\Facades\Schema::hasTable('ecommerce_cms_blog_categories')) {
            return Inertia::render('Ecommerce/Cms/Blog/Categories', ['categories' => []]);
        }

        $categories = CmsBlogCategoryModel::where('shop_id', $shopId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug, 'description' => $c->description, 'sort_order' => $c->sort_order]);

        return Inertia::render('Ecommerce/Cms/Blog/Categories', ['categories' => $categories]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $shopId = $this->getShopId($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['shop_id'] = $shopId;
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);

        CmsBlogCategoryModel::create($validated);

        return redirect()->route('ecommerce.cms.blog.categories.index')->with('success', 'Catégorie créée.');
    }

    public function update(Request $request, string $id): \Illuminate\Http\RedirectResponse
    {
        $shopId = $this->getShopId($request);

        $cat = CmsBlogCategoryModel::where('shop_id', $shopId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);

        $cat->update($validated);

        return redirect()->route('ecommerce.cms.blog.categories.index')->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(Request $request, string $id): \Illuminate\Http\RedirectResponse
    {
        $shopId = $this->getShopId($request);

        $cat = CmsBlogCategoryModel::where('shop_id', $shopId)->findOrFail($id);
        $cat->delete();

        return redirect()->route('ecommerce.cms.blog.categories.index')->with('success', 'Catégorie supprimée.');
    }
}
