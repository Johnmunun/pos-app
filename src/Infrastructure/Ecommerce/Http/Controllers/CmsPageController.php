<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Ecommerce\Models\CmsMediaModel;
use Src\Infrastructure\Ecommerce\Models\CmsPageModel;
use Src\Infrastructure\Ecommerce\Services\DefaultCmsPagesService;

class CmsPageController
{
    private function getPublicMediaUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            return null;
        }

        return $disk->url($path);
    }

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

        if (!Schema::hasTable('ecommerce_cms_pages')) {
            return Inertia::render('Ecommerce/Cms/Pages/Index', ['pages' => [], 'media' => [], 'flash' => $request->session()->get('flash', [])]);
        }

        $pages = CmsPageModel::where('shop_id', $shopId)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'template' => $p->template ?? 'standard',
                'content' => $p->content,
                'image_path' => $p->image_path,
                'metadata' => $p->metadata ?? [],
                'is_active' => $p->is_active,
                'published_at' => $p->published_at?->format('Y-m-d H:i'),
                'sort_order' => $p->sort_order,
            ]);

        $media = [];
        if (Schema::hasTable('ecommerce_cms_media')) {
            $media = CmsMediaModel::where('shop_id', $shopId)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'file_path' => $m->file_path,
                    'url' => $this->getPublicMediaUrl($m->file_path),
                    'file_type' => $m->file_type,
                ])
                ->toArray();
        }

        return Inertia::render('Ecommerce/Cms/Pages/Index', [
            'pages' => $pages,
            'media' => $media,
        ]);
    }

    /**
     * Crée les pages par défaut (À propos, Contact, CGV, Confidentialité) si elles n'existent pas.
     */
    public function createDefaults(Request $request): \Illuminate\Http\RedirectResponse
    {
        $shopId = (int) $this->getShopId($request);

        if (!Schema::hasTable('ecommerce_cms_pages')) {
            return redirect()->route('ecommerce.cms.pages.index')->with('error', 'Table CMS indisponible.');
        }

        $defaults = DefaultCmsPagesService::getDefaultPagesContent();
        $created = 0;

        foreach ($defaults as $order => $pageData) {
            $exists = CmsPageModel::where('shop_id', $shopId)->where('slug', $pageData['slug'])->exists();
            if (!$exists) {
                CmsPageModel::create(array_merge($pageData, ['shop_id' => $shopId, 'sort_order' => $order]));
                $created++;
            }
        }

        $msg = $created > 0
            ? "{$created} page(s) par défaut créée(s). Vous pouvez modifier le texte et les images."
            : 'Toutes les pages par défaut existent déjà.';

        return redirect()->route('ecommerce.cms.pages.index')->with('success', $msg);
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
            'template' => 'nullable|string|in:standard,contact',
            'content' => 'nullable|string',
            'image_path' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
            'metadata.address' => 'nullable|string|max:500',
            'metadata.phone' => 'nullable|string|max:100',
            'metadata.email' => 'nullable|string|max:255',
            'metadata.hours' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        $validated['shop_id'] = $shopId;
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['metadata'] = $validated['metadata'] ?? null;

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
            'template' => 'nullable|string|in:standard,contact',
            'content' => 'nullable|string',
            'image_path' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
            'metadata.address' => 'nullable|string|max:500',
            'metadata.phone' => 'nullable|string|max:100',
            'metadata.email' => 'nullable|string|max:255',
            'metadata.hours' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['metadata'] = $validated['metadata'] ?? null;

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
