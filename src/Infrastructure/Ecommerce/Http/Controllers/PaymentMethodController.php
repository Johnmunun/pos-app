<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Ecommerce\Models\PaymentMethodModel;

class PaymentMethodController
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

        $methods = PaymentMethodModel::where('shop_id', $shopId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Ecommerce/Payments/Index', [
            'methods' => $methods->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'code' => $m->code,
                'type' => $m->type,
                'fee_percentage' => (float) $m->fee_percentage,
                'fee_fixed' => (float) $m->fee_fixed,
                'is_active' => $m->is_active,
                'is_default' => $m->is_default,
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Ecommerce/Payments/Form', [
            'method' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $shopId = $this->getShopId($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'type' => 'required|string|in:card,wallet,bank_transfer,cash_on_delivery,other',
            'fee_percentage' => 'nullable|numeric|min:0',
            'fee_fixed' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        $code = preg_replace('/[^a-z0-9_]/', '', strtolower($validated['code']));
        $code = $code ?: 'method_' . $shopId . '_' . time();

        if (PaymentMethodModel::where('code', $code)->exists()) {
            $code = $code . '_' . $shopId;
        }

        PaymentMethodModel::create([
            'shop_id' => $shopId,
            'name' => $validated['name'],
            'code' => $code,
            'type' => $validated['type'],
            'fee_percentage' => $validated['fee_percentage'] ?? 0,
            'fee_fixed' => $validated['fee_fixed'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return redirect()->route('ecommerce.payments.index')->with('success', 'Méthode de paiement créée.');
    }

    public function edit(Request $request, string $id): Response|RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $method = PaymentMethodModel::where('shop_id', $shopId)->findOrFail($id);

        return Inertia::render('Ecommerce/Payments/Form', [
            'method' => [
                'id' => $method->id,
                'name' => $method->name,
                'code' => $method->code,
                'type' => $method->type,
                'fee_percentage' => (float) $method->fee_percentage,
                'fee_fixed' => (float) $method->fee_fixed,
                'is_active' => $method->is_active,
                'is_default' => $method->is_default,
            ],
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $method = PaymentMethodModel::where('shop_id', $shopId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:card,wallet,bank_transfer,cash_on_delivery,other',
            'fee_percentage' => 'nullable|numeric|min:0',
            'fee_fixed' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        if (!empty($validated['is_default'])) {
            PaymentMethodModel::where('shop_id', $shopId)->update(['is_default' => false]);
        }

        $method->update($validated);

        return redirect()->route('ecommerce.payments.index')->with('success', 'Méthode de paiement mise à jour.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $method = PaymentMethodModel::where('shop_id', $shopId)->findOrFail($id);
        $method->delete();
        return redirect()->route('ecommerce.payments.index')->with('success', 'Méthode supprimée.');
    }
}
