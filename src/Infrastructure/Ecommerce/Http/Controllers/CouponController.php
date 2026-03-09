<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Ecommerce\Models\CouponModel;

class CouponController
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

        $coupons = CouponModel::where('shop_id', $shopId)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Ecommerce/Coupons/Index', [
            'coupons' => $coupons->map(fn ($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
                'type' => $c->type,
                'discount_value' => (float) $c->discount_value,
                'minimum_purchase' => $c->minimum_purchase ? (float) $c->minimum_purchase : null,
                'maximum_uses' => $c->maximum_uses,
                'used_count' => $c->used_count,
                'starts_at' => $c->starts_at?->toIso8601String(),
                'ends_at' => $c->ends_at?->toIso8601String(),
                'is_active' => $c->is_active,
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Ecommerce/Coupons/Form', [
            'coupon' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $shopId = $this->getShopId($request);

        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:percentage,fixed_amount,free_shipping',
            'discount_value' => 'required|numeric|min:0',
            'minimum_purchase' => 'nullable|numeric|min:0',
            'maximum_uses' => 'nullable|integer|min:0',
            'maximum_uses_per_customer' => 'nullable|integer|min:0',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        $code = strtoupper(preg_replace('/\s+/', '', $validated['code']));
        if (CouponModel::where('code', $code)->exists()) {
            return redirect()->back()->withInput()->withErrors(['code' => 'Ce code existe déjà.']);
        }

        CouponModel::create([
            'shop_id' => $shopId,
            'code' => $code,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'discount_value' => $validated['discount_value'],
            'minimum_purchase' => $validated['minimum_purchase'] ?? null,
            'maximum_uses' => $validated['maximum_uses'] ?? null,
            'maximum_uses_per_customer' => $validated['maximum_uses_per_customer'] ?? null,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('ecommerce.coupons.index')->with('success', 'Coupon créé.');
    }

    public function edit(Request $request, string $id): Response|RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $c = CouponModel::where('shop_id', $shopId)->findOrFail($id);

        return Inertia::render('Ecommerce/Coupons/Form', [
            'coupon' => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
                'description' => $c->description ?? '',
                'type' => $c->type,
                'discount_value' => (float) $c->discount_value,
                'minimum_purchase' => $c->minimum_purchase ? (float) $c->minimum_purchase : null,
                'maximum_uses' => $c->maximum_uses,
                'maximum_uses_per_customer' => $c->maximum_uses_per_customer,
                'starts_at' => $c->starts_at?->format('Y-m-d\TH:i'),
                'ends_at' => $c->ends_at?->format('Y-m-d\TH:i'),
                'is_active' => $c->is_active,
            ],
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $c = CouponModel::where('shop_id', $shopId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:percentage,fixed_amount,free_shipping',
            'discount_value' => 'required|numeric|min:0',
            'minimum_purchase' => 'nullable|numeric|min:0',
            'maximum_uses' => 'nullable|integer|min:0',
            'maximum_uses_per_customer' => 'nullable|integer|min:0',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        $c->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'discount_value' => $validated['discount_value'],
            'minimum_purchase' => $validated['minimum_purchase'] ?? null,
            'maximum_uses' => $validated['maximum_uses'] ?? null,
            'maximum_uses_per_customer' => $validated['maximum_uses_per_customer'] ?? null,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('ecommerce.coupons.index')->with('success', 'Coupon mis à jour.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $c = CouponModel::where('shop_id', $shopId)->findOrFail($id);
        $c->delete();
        return redirect()->route('ecommerce.coupons.index')->with('success', 'Coupon supprimé.');
    }
}
