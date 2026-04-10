<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Billing\Services\FeatureLimitService;

class MarketingController
{
    public function __construct(
        private readonly FeatureLimitService $featureLimitService
    ) {
    }

    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'User not authenticated.');
        }

        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        $isRoot = \App\Models\User::find($user->id)?->isRoot() ?? false;

        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found.');
        }
        if ($isRoot && !$shopId) {
            abort(403, 'Please select a shop first.');
        }

        return (string) $shopId;
    }

    private function tenantIdForShop(Shop $shop): string
    {
        return $shop->tenant_id !== null ? (string) $shop->tenant_id : (string) $shop->id;
    }

    private function resolveShop(string $shopId, ?string $tenantId = null): ?Shop
    {
        $shop = Shop::query()->find($shopId);
        if ($shop !== null) {
            return $shop;
        }

        if ($tenantId !== null && $tenantId !== '') {
            return Shop::query()
                ->where('tenant_id', $tenantId)
                ->orderByDesc('id')
                ->first();
        }

        return null;
    }

    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $tenantId = $request->user()?->tenant_id ? (string) $request->user()?->tenant_id : null;
        $shop = $this->resolveShop($shopId, $tenantId);

        if (!$shop) {
            abort(404, 'Shop not found');
        }

        $config = $shop->ecommerce_storefront_config ?? [];
        if (!is_array($config)) {
            $config = [];
        }

        $tenantId = $this->tenantIdForShop($shop);
        $marketingPro = $this->featureLimitService->isFeatureEnabled($tenantId, 'ecommerce.marketing.pro');
        $audienceAnalytics = $this->featureLimitService->isFeatureEnabled($tenantId, 'analytics.advanced');

        $marketing = [
            'seo_title' => $config['seo_title'] ?? null,
            'seo_description' => $config['seo_description'] ?? null,
            'seo_keywords' => $config['seo_keywords'] ?? null,
            'seo_indexing_enabled' => (bool) ($config['seo_indexing_enabled'] ?? true),
            'facebook_pixel_id' => $marketingPro ? ($config['facebook_pixel_id'] ?? null) : null,
            'tiktok_pixel_id' => $marketingPro ? ($config['tiktok_pixel_id'] ?? null) : null,
            'google_analytics_id' => $marketingPro ? ($config['google_analytics_id'] ?? null) : null,
            'google_tag_manager_id' => $marketingPro ? ($config['google_tag_manager_id'] ?? null) : null,
            'meta_verification' => $marketingPro ? ($config['meta_verification'] ?? null) : null,
            'marketing_notes' => $config['marketing_notes'] ?? null,
        ];

        return Inertia::render('Ecommerce/Marketing/Index', [
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
            ],
            'marketing' => $marketing,
            'marketingProEnabled' => $marketingPro,
            'audienceAnalyticsEnabled' => $audienceAnalytics,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $tenantId = $request->user()?->tenant_id ? (string) $request->user()?->tenant_id : null;
        $shop = $this->resolveShop($shopId, $tenantId);

        if (!$shop) {
            abort(404, 'Shop not found');
        }

        $tenantId = $this->tenantIdForShop($shop);
        $marketingPro = $this->featureLimitService->isFeatureEnabled($tenantId, 'ecommerce.marketing.pro');

        $rules = [
            'seo_title' => 'nullable|string|max:150',
            'seo_description' => 'nullable|string|max:255',
            'seo_keywords' => 'nullable|string|max:255',
            'seo_indexing_enabled' => 'sometimes|boolean',
            'marketing_notes' => 'nullable|string|max:1000',
        ];
        if ($marketingPro) {
            $rules['facebook_pixel_id'] = 'nullable|string|max:100';
            $rules['tiktok_pixel_id'] = 'nullable|string|max:100';
            $rules['google_analytics_id'] = 'nullable|string|max:100';
            $rules['google_tag_manager_id'] = 'nullable|string|max:20';
            $rules['meta_verification'] = 'nullable|string|max:255';
        }

        $data = $request->validate($rules);

        $current = $shop->ecommerce_storefront_config ?? [];
        if (!is_array($current)) {
            $current = [];
        }

        if (!array_key_exists('seo_indexing_enabled', $data)) {
            $data['seo_indexing_enabled'] = $current['seo_indexing_enabled'] ?? true;
        }

        if (!$marketingPro) {
            unset(
                $data['facebook_pixel_id'],
                $data['tiktok_pixel_id'],
                $data['google_analytics_id'],
                $data['google_tag_manager_id'],
                $data['meta_verification']
            );
        }

        $shop->ecommerce_storefront_config = array_merge($current, $data);
        $shop->save();

        return redirect()
            ->route('ecommerce.marketing.index')
            ->with('success', 'Paramètres marketing mis à jour.');
    }
}
