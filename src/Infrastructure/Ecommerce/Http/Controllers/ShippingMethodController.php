<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Ecommerce\Models\ShippingMethodModel;

class ShippingMethodController
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

        $methods = ShippingMethodModel::where('shop_id', $shopId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Ecommerce/Shipping/Index', [
            'methods' => $methods->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'type' => $m->type,
                'base_cost' => (float) $m->base_cost,
                'free_shipping_threshold' => $m->free_shipping_threshold ? (float) $m->free_shipping_threshold : null,
                'estimated_days_min' => $m->estimated_days_min,
                'estimated_days_max' => $m->estimated_days_max,
                'is_active' => $m->is_active,
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Ecommerce/Shipping/Form', [
            'method' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $shopId = $this->getShopId($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:flat_rate,weight_based,price_based,free',
            'base_cost' => 'nullable|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'estimated_days_min' => 'nullable|integer|min:0',
            'estimated_days_max' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        ShippingMethodModel::create([
            'shop_id' => $shopId,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'base_cost' => $validated['base_cost'] ?? 0,
            'free_shipping_threshold' => $validated['free_shipping_threshold'] ?? null,
            'estimated_days_min' => $validated['estimated_days_min'] ?? null,
            'estimated_days_max' => $validated['estimated_days_max'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('ecommerce.shipping.index')->with('success', 'Méthode de livraison créée.');
    }

    public function edit(Request $request, string $id): Response|RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $method = ShippingMethodModel::where('shop_id', $shopId)->findOrFail($id);

        return Inertia::render('Ecommerce/Shipping/Form', [
            'method' => [
                'id' => $method->id,
                'name' => $method->name,
                'type' => $method->type,
                'base_cost' => (float) $method->base_cost,
                'free_shipping_threshold' => $method->free_shipping_threshold ? (float) $method->free_shipping_threshold : null,
                'estimated_days_min' => $method->estimated_days_min,
                'estimated_days_max' => $method->estimated_days_max,
                'is_active' => $method->is_active,
            ],
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $method = ShippingMethodModel::where('shop_id', $shopId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:flat_rate,weight_based,price_based,free',
            'base_cost' => 'nullable|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'estimated_days_min' => 'nullable|integer|min:0',
            'estimated_days_max' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $method->update($validated);

        return redirect()->route('ecommerce.shipping.index')->with('success', 'Méthode de livraison mise à jour.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $method = ShippingMethodModel::where('shop_id', $shopId)->findOrFail($id);
        $method->delete();
        return redirect()->route('ecommerce.shipping.index')->with('success', 'Méthode supprimée.');
    }
}
