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
            ] : null,
        ]);
    }

    // Plus de mise à jour de devise ici : la devise est gérée globalement via /settings/currencies
}
