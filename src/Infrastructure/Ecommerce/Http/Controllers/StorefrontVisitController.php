<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Src\Application\Billing\Services\FeatureLimitService;
use Src\Infrastructure\Ecommerce\Services\StorefrontGeoResolver;

/**
 * Point de collecte audience vitrine (sous-domaine public uniquement).
 */
class StorefrontVisitController
{
    public function __invoke(Request $request, FeatureLimitService $featureLimitService): Response
    {
        $shop = $request->attributes->get('storefront_shop');
        if (!$shop instanceof Shop) {
            abort(404);
        }

        $tenantId = $shop->tenant_id !== null ? (string) $shop->tenant_id : (string) $shop->id;
        if (!$featureLimitService->isFeatureEnabled($tenantId, 'analytics.advanced')) {
            return response()->noContent();
        }
        if (!Schema::hasTable('ecommerce_storefront_visits')) {
            return response()->noContent();
        }

        $validated = $request->validate([
            'path' => 'nullable|string|max:500',
            'title' => 'nullable|string|max:200',
        ]);

        $geo = StorefrontGeoResolver::forRequest($request);
        $path = isset($validated['path']) ? mb_substr((string) $validated['path'], 0, 500) : null;

        DB::table('ecommerce_storefront_visits')->insert([
            'shop_id' => $shop->id,
            'country_code' => $geo['country_code'],
            'region_name' => $geo['region'],
            'city' => $geo['city'],
            'path' => $path,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->noContent();
    }
}
