<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Ecommerce\Models\ReviewModel;

class ReviewController
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

        $reviews = ReviewModel::where('ecommerce_reviews.shop_id', $shopId)
            ->leftJoin('gc_products', 'ecommerce_reviews.product_id', '=', 'gc_products.id')
            ->select('ecommerce_reviews.*', 'gc_products.name as product_name')
            ->orderBy('ecommerce_reviews.created_at', 'desc')
            ->paginate(20)
            ->through(fn ($r) => [
                'id' => $r->id,
                'product_id' => $r->product_id,
                'product_name' => $r->product_name ?? '-',
                'customer_name' => $r->customer_name,
                'rating' => $r->rating,
                'title' => $r->title,
                'comment' => $r->comment ? \Str::limit($r->comment, 80) : null,
                'is_approved' => $r->is_approved,
                'is_featured' => $r->is_featured,
                'created_at' => $r->created_at?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Ecommerce/Reviews/Index', [
            'reviews' => $reviews,
        ]);
    }

    public function create(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $products = \Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel::where('shop_id', $shopId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])
            ->toArray();

        return Inertia::render('Ecommerce/Reviews/Form', [
            'review' => null,
            'products' => $products,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $shopId = $this->getShopId($request);

        $validated = $request->validate([
            'product_id' => 'required|string',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string',
            'is_approved' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        ReviewModel::create([
            'shop_id' => $shopId,
            'product_id' => $validated['product_id'],
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'] ?? null,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'] ?? null,
            'is_approved' => $validated['is_approved'] ?? false,
            'is_featured' => $validated['is_featured'] ?? false,
        ]);

        return redirect()->route('ecommerce.reviews.index')->with('success', 'Avis créé.');
    }

    public function edit(Request $request, string $id): Response|RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $r = ReviewModel::where('shop_id', $shopId)->findOrFail($id);

        $products = \Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel::where('shop_id', $shopId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])
            ->toArray();

        return Inertia::render('Ecommerce/Reviews/Form', [
            'review' => [
                'id' => $r->id,
                'product_id' => $r->product_id,
                'customer_name' => $r->customer_name,
                'customer_email' => $r->customer_email ?? '',
                'rating' => $r->rating,
                'title' => $r->title ?? '',
                'comment' => $r->comment ?? '',
                'is_approved' => $r->is_approved,
                'is_featured' => $r->is_featured,
            ],
            'products' => $products,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $r = ReviewModel::where('shop_id', $shopId)->findOrFail($id);

        $validated = $request->validate([
            'product_id' => 'required|string',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string',
            'is_approved' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $r->update($validated);

        return redirect()->route('ecommerce.reviews.index')->with('success', 'Avis mis à jour.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $r = ReviewModel::where('shop_id', $shopId)->findOrFail($id);
        $r->delete();
        return redirect()->route('ecommerce.reviews.index')->with('success', 'Avis supprimé.');
    }
}
