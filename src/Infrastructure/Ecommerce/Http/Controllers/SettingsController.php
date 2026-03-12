<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController
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
        $shop = Shop::find($shopId);

        return Inertia::render('Ecommerce/Settings/Index', [
            'shop' => $shop ? [
                'id' => $shop->id,
                'name' => $shop->name,
                'currency' => $shop->currency ?? 'USD',
                'default_tax_rate' => $shop->default_tax_rate ? (float) $shop->default_tax_rate : 0,
                'address' => $shop->address ?? '',
                'city' => $shop->city ?? '',
                'country' => $shop->country ?? '',
                'phone' => $shop->phone ?? '',
                'email' => $shop->email ?? '',
                'ecommerce_subdomain' => $shop->ecommerce_subdomain,
                'ecommerce_is_online' => (bool) $shop->ecommerce_is_online,
            ] : null,
        ]);
    }

    /**
     * Met à jour le sous-domaine de la boutique (ex: kasashop pour kasashop.omnisolution.shop).
     */
    public function updateDomain(Request $request): RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $shop = Shop::find($shopId);
        if (!$shop) {
            abort(404, 'Shop not found');
        }

        $data = $request->validate([
            'subdomain' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/i'],
            'is_online' => ['sometimes', 'boolean'],
        ]);

        // Vérifier l'unicité du sous-domaine
        $exists = Shop::where('id', '!=', $shop->id)
            ->where('ecommerce_subdomain', $data['subdomain'])
            ->exists();

        if ($exists) {
            return redirect()
                ->route('ecommerce.settings.index')
                ->withErrors(['subdomain' => 'Ce sous-domaine est déjà utilisé par une autre boutique.']);
        }

        $shop->ecommerce_subdomain = $data['subdomain'];
        if (array_key_exists('is_online', $data)) {
            $shop->ecommerce_is_online = (bool) $data['is_online'];
        }
        $shop->save();

        return redirect()
            ->route('ecommerce.settings.index')
            ->with('success', 'Domaine de la boutique mis à jour.');
    }

    // Plus de mise à jour de devise ici : la devise est gérée globalement via /settings/currencies
}
