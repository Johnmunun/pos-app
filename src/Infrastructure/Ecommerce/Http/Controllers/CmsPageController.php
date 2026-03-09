<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Ecommerce\Models\CmsPageModel;

class CmsPageController
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

        if (!\Illuminate\Support\Facades\Schema::hasTable('ecommerce_cms_pages')) {
            return Inertia::render('Ecommerce/Cms/Pages/Index', ['pages' => [], 'flash' => $request->session()->get('flash', [])]);
        }

        $pages = CmsPageModel::where('shop_id', $shopId)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'content' => $p->content,
                'image_path' => $p->image_path,
                'is_active' => $p->is_active,
                'published_at' => $p->published_at?->format('Y-m-d H:i'),
                'sort_order' => $p->sort_order,
            ]);

        return Inertia::render('Ecommerce/Cms/Pages/Index', [
            'pages' => $pages,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->getShopId($request);
        return Inertia::render('Ecommerce/Cms/Pages/Form', ['page' => null]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $shopId = $this->getShopId($request);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'image_path' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        $validated['shop_id'] = $shopId;
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        CmsPageModel::create($validated);

        return redirect()->route('ecommerce.cms.pages.index')->with('success', 'Page créée.');
    }

    public function edit(Request $request, string $id): Response
    {
        $shopId = $this->getShopId($request);

        $page = CmsPageModel::where('shop_id', $shopId)->findOrFail($id);

        return Inertia::render('Ecommerce/Cms/Pages/Form', [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'content' => $page->content,
                'image_path' => $page->image_path,
                'is_active' => $page->is_active,
                'published_at' => $page->published_at?->format('Y-m-d\TH:i'),
            ],
        ]);
    }

    public function update(Request $request, string $id): \Illuminate\Http\RedirectResponse
    {
        $shopId = $this->getShopId($request);

        $page = CmsPageModel::where('shop_id', $shopId)->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'image_path' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        $page->update($validated);

        return redirect()->route('ecommerce.cms.pages.index')->with('success', 'Page mise à jour.');
    }

    public function destroy(Request $request, string $id): \Illuminate\Http\RedirectResponse
    {
        $shopId = $this->getShopId($request);

        $page = CmsPageModel::where('shop_id', $shopId)->findOrFail($id);
        $page->delete();

        return redirect()->route('ecommerce.cms.pages.index')->with('success', 'Page supprimée.');
    }
}
