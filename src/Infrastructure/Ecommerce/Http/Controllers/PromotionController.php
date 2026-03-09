<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Ecommerce\Models\PromotionModel;

class PromotionController
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

        $promotions = PromotionModel::where('shop_id', $shopId)
            ->orderBy('starts_at', 'desc')
            ->get();

        return Inertia::render('Ecommerce/Promotions/Index', [
            'promotions' => $promotions->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'type' => $p->type,
                'discount_value' => $p->discount_value ? (float) $p->discount_value : null,
                'minimum_purchase' => $p->minimum_purchase ? (float) $p->minimum_purchase : null,
                'maximum_uses' => $p->maximum_uses,
                'used_count' => $p->used_count,
                'starts_at' => $p->starts_at?->toIso8601String(),
                'ends_at' => $p->ends_at?->toIso8601String(),
                'is_active' => $p->is_active,
            ]),
        ]);
    }

    private function getProductsAndCategories(string $shopId): array
    {
        $products = \Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel::where('shop_id', $shopId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])
            ->toArray();
        $categories = \Src\Infrastructure\GlobalCommerce\Inventory\Models\CategoryModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ->toArray();
        return [$products, $categories];
    }

    public function create(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        [$products, $categories] = $this->getProductsAndCategories($shopId);
        return Inertia::render('Ecommerce/Promotions/Form', [
            'promotion' => null,
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $shopId = $this->getShopId($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:percentage,fixed_amount,buy_x_get_y,free_shipping',
            'discount_value' => 'nullable|numeric|min:0',
            'buy_quantity' => 'nullable|integer|min:1',
            'get_quantity' => 'nullable|integer|min:1',
            'minimum_purchase' => 'nullable|numeric|min:0',
            'maximum_uses' => 'nullable|integer|min:0',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
            'applicable_products' => 'nullable|array',
            'applicable_products.*' => 'string',
            'applicable_categories' => 'nullable|array',
            'applicable_categories.*' => 'string',
        ]);

        PromotionModel::create([
            'shop_id' => $shopId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'discount_value' => $validated['discount_value'] ?? null,
            'buy_quantity' => $validated['buy_quantity'] ?? null,
            'get_quantity' => $validated['get_quantity'] ?? null,
            'minimum_purchase' => $validated['minimum_purchase'] ?? null,
            'maximum_uses' => $validated['maximum_uses'] ?? null,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'is_active' => $validated['is_active'] ?? true,
            'applicable_products' => !empty($validated['applicable_products']) ? $validated['applicable_products'] : null,
            'applicable_categories' => !empty($validated['applicable_categories']) ? $validated['applicable_categories'] : null,
        ]);

        return redirect()->route('ecommerce.promotions.index')->with('success', 'Promotion créée.');
    }

    public function edit(Request $request, string $id): Response|RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $p = PromotionModel::where('shop_id', $shopId)->findOrFail($id);
        [$products, $categories] = $this->getProductsAndCategories($shopId);

        return Inertia::render('Ecommerce/Promotions/Form', [
            'promotion' => [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description ?? '',
                'type' => $p->type,
                'discount_value' => $p->discount_value ? (float) $p->discount_value : null,
                'buy_quantity' => $p->buy_quantity,
                'get_quantity' => $p->get_quantity,
                'minimum_purchase' => $p->minimum_purchase ? (float) $p->minimum_purchase : null,
                'maximum_uses' => $p->maximum_uses,
                'starts_at' => $p->starts_at?->format('Y-m-d\TH:i'),
                'ends_at' => $p->ends_at?->format('Y-m-d\TH:i'),
                'is_active' => $p->is_active,
                'applicable_products' => $p->applicable_products ?? [],
                'applicable_categories' => $p->applicable_categories ?? [],
            ],
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $p = PromotionModel::where('shop_id', $shopId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:percentage,fixed_amount,buy_x_get_y,free_shipping',
            'discount_value' => 'nullable|numeric|min:0',
            'buy_quantity' => 'nullable|integer|min:1',
            'get_quantity' => 'nullable|integer|min:1',
            'minimum_purchase' => 'nullable|numeric|min:0',
            'maximum_uses' => 'nullable|integer|min:0',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
            'applicable_products' => 'nullable|array',
            'applicable_products.*' => 'string',
            'applicable_categories' => 'nullable|array',
            'applicable_categories.*' => 'string',
        ]);

        $p->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'discount_value' => $validated['discount_value'] ?? null,
            'buy_quantity' => $validated['buy_quantity'] ?? null,
            'get_quantity' => $validated['get_quantity'] ?? null,
            'minimum_purchase' => $validated['minimum_purchase'] ?? null,
            'maximum_uses' => $validated['maximum_uses'] ?? null,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'is_active' => $validated['is_active'] ?? true,
            'applicable_products' => !empty($validated['applicable_products']) ? $validated['applicable_products'] : null,
            'applicable_categories' => !empty($validated['applicable_categories']) ? $validated['applicable_categories'] : null,
        ]);

        return redirect()->route('ecommerce.promotions.index')->with('success', 'Promotion mise à jour.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $p = PromotionModel::where('shop_id', $shopId)->findOrFail($id);
        $p->delete();
        return redirect()->route('ecommerce.promotions.index')->with('success', 'Promotion supprimée.');
    }
}
