<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
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

    /**
     * Devise et taux de change depuis les paramètres (settings/currencies).
     */
    private function getCurrencyAndRates(string $tenantId): array
    {
        $currenciesList = Currency::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->get();
        $defaultCurrencyModel = $currenciesList->firstWhere('is_default', true) ?? $currenciesList->first();
        $defaultCode = $defaultCurrencyModel ? strtoupper($defaultCurrencyModel->code) : 'USD';

        $exchangeRatesMap = [$defaultCode => 1.0];
        if ($defaultCurrencyModel) {
            foreach ($currenciesList as $c) {
                $code = strtoupper($c->code);
                if ($code === $defaultCode) {
                    continue;
                }
                $fromDefault = ExchangeRate::where('tenant_id', $tenantId)
                    ->where('from_currency_id', $defaultCurrencyModel->id)
                    ->where('to_currency_id', $c->id)
                    ->orderByDesc('effective_date')
                    ->first();
                if ($fromDefault && (float) $fromDefault->rate > 0) {
                    $exchangeRatesMap[$code] = (float) $fromDefault->rate;
                } else {
                    $toDefault = ExchangeRate::where('tenant_id', $tenantId)
                        ->where('from_currency_id', $c->id)
                        ->where('to_currency_id', $defaultCurrencyModel->id)
                        ->orderByDesc('effective_date')
                        ->first();
                    if ($toDefault && (float) $toDefault->rate > 0) {
                        $exchangeRatesMap[$code] = 1.0 / (float) $toDefault->rate;
                    } else {
                        $exchangeRatesMap[$code] = 1.0;
                    }
                }
            }
        }

        return ['currency' => $defaultCode, 'exchange_rates' => $exchangeRatesMap];
    }

    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $tenantId = (int) ($request->user()?->tenant_id ?? $shopId);

        $shop = \App\Models\Shop::find($shopId);
        $taxRate = $shop && $shop->default_tax_rate ? (float) $shop->default_tax_rate : 0;

        $currencyConfig = $this->getCurrencyAndRates((string) $tenantId);
        $currency = $currencyConfig['currency'];
        $exchangeRates = $currencyConfig['exchange_rates'];

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
