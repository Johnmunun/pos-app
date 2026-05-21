<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Currency\Services\TenantDisplayCurrencyService;
use Src\Infrastructure\Ecommerce\Models\PaymentMethodModel;
use Src\Infrastructure\Ecommerce\Models\ShippingMethodModel;

class CartController
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
        $user = $request->user();
        $tenantId = $user && $user->tenant_id !== null ? (int) $user->tenant_id : (int) $shopId;

        $shop = \App\Models\Shop::find($shopId);
        $taxRate = $shop && $shop->default_tax_rate ? (float) $shop->default_tax_rate : 0;

        $currencyBundle = app(TenantDisplayCurrencyService::class)->resolve(
            $request,
            (string) $tenantId,
            $shop,
            false
        );
        $currency = $currencyBundle['currency'];
        $exchangeRates = $currencyBundle['exchange_rates'];

        $shippingMethods = ShippingMethodModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'base_cost', 'free_shipping_threshold', 'estimated_days_min', 'estimated_days_max'])
            ->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'type' => $m->type,
                'base_cost' => (float) $m->base_cost,
                'free_shipping_threshold' => $m->free_shipping_threshold ? (float) $m->free_shipping_threshold : null,
                'estimated_days_min' => $m->estimated_days_min,
                'estimated_days_max' => $m->estimated_days_max,
            ])
            ->values()
            ->toArray();

        $paymentMethods = PaymentMethodModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type'])
            ->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'code' => $m->code,
                'type' => $m->type,
            ])
            ->values()
            ->toArray();

        $products = $this->getProductsForOrderDrawer($shopId);

        return Inertia::render('Ecommerce/Cart/Index', [
            'shipping_methods' => $shippingMethods,
            'payment_methods' => $paymentMethods,
            'tax_rate' => $taxRate,
            'currency' => $currency,
            'exchange_rates' => $exchangeRates,
            'products' => $products,
        ]);
    }

    private function getProductsForOrderDrawer(string $shopId): array
    {
        $products = \Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel::where('shop_id', $shopId)
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(200)
            ->get();

        $imageService = app(\Src\Infrastructure\GlobalCommerce\Services\ProductImageService::class);
        return $products->map(function ($p) use ($imageService) {
            $url = null;
            if ($p->image_path) {
                try {
                    $url = $imageService->getUrl($p->image_path, $p->image_type ?: 'upload');
                } catch (\Throwable $e) {
                    //
                }
            }
            return [
                'id' => $p->id,
                'name' => $p->name,
                'price_amount' => (float) ($p->sale_price_amount ?? $p->purchase_price_amount ?? 0),
                'price_currency' => $p->sale_price_currency ?? $p->purchase_price_currency ?? 'USD',
                'image_url' => $url,
            ];
        })->toArray();
    }
}
