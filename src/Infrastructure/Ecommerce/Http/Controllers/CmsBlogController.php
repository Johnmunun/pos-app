<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Ecommerce\Models\CmsBlogArticleModel;
use Src\Infrastructure\Ecommerce\Models\CmsBlogCategoryModel;

class CmsBlogController
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

        if (!\Illuminate\Support\Facades\Schema::hasTable('ecommerce_cms_blog_articles')) {
            return Inertia::render('Ecommerce/Cms/Blog/Index', ['articles' => [], 'categories' => []]);
        }

        /** @phpstan-ignore-next-line Larastan relation false positive for category */
        $articles = CmsBlogArticleModel::where('shop_id', $shopId)
            ->with('category')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'slug' => $a->slug,
                'content' => $a->content,
                'excerpt' => $a->excerpt,
                'image_path' => $a->image_path,
                'category_id' => $a->category_id,
                'category_name' => $a->category?->name,
                'is_active' => $a->is_active,
                'published_at' => $a->published_at?->format('Y-m-d H:i'),
            ]);

        $categories = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('ecommerce_cms_blog_categories')) {
            $categories = CmsBlogCategoryModel::where('shop_id', $shopId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug])
                ->toArray();
        }

        return Inertia::render('Ecommerce/Cms/Blog/Index', [
            'articles' => $articles,
            'categories' => $categories,
        ]);
    }

    public function create(Request $request): Response
    {
        $shopId = $this->getShopId($request);

        $categories = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('ecommerce_cms_blog_categories')) {
            $categories = CmsBlogCategoryModel::where('shop_id', $shopId)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->toArray();
        }

        return Inertia::render('Ecommerce/Cms/Blog/Form', ['article' => null, 'categories' => $categories]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $request->merge(['category_id' => $request->input('category_id') ?: null]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'image_path' => 'nullable|string|max:500',
            'excerpt' => 'nullable|string|max:500',
            'category_id' => 'nullable|uuid|exists:ecommerce_cms_blog_categories,id',
            'is_active' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        $validated['shop_id'] = $shopId;
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['category_id'] = $validated['category_id'] ?? null;

        CmsBlogArticleModel::create($validated);

        return redirect()->route('ecommerce.cms.blog.index')->with('success', 'Article créé.');
    }

    public function edit(Request $request, string $id): Response
    {
        $shopId = $this->getShopId($request);

        $article = CmsBlogArticleModel::where('shop_id', $shopId)->findOrFail($id);

        $categories = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('ecommerce_cms_blog_categories')) {
            $categories = CmsBlogCategoryModel::where('shop_id', $shopId)->orderBy('name')->get(['id', 'name'])->toArray();
        }

        return Inertia::render('Ecommerce/Cms/Blog/Form', [
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'content' => $article->content,
                'image_path' => $article->image_path,
                'excerpt' => $article->excerpt,
                'category_id' => $article->category_id,
                'is_active' => $article->is_active,
                'published_at' => $article->published_at?->format('Y-m-d\TH:i'),
            ],
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, string $id): \Illuminate\Http\RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $request->merge(['category_id' => $request->input('category_id') ?: null]);

        $article = CmsBlogArticleModel::where('shop_id', $shopId)->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'image_path' => 'nullable|string|max:500',
            'excerpt' => 'nullable|string|max:500',
            'category_id' => 'nullable|uuid|exists:ecommerce_cms_blog_categories,id',
            'is_active' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        $article->update($validated);

        return redirect()->route('ecommerce.cms.blog.index')->with('success', 'Article mis à jour.');
    }

    public function destroy(Request $request, string $id): \Illuminate\Http\RedirectResponse
    {
        $shopId = $this->getShopId($request);

        $article = CmsBlogArticleModel::where('shop_id', $shopId)->findOrFail($id);
        $article->delete();

        return redirect()->route('ecommerce.cms.blog.index')->with('success', 'Article supprimé.');
    }
}
