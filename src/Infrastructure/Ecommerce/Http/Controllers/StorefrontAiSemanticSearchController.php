<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\Billing\Services\FeatureLimitService;
use Src\Application\Ecommerce\Services\StorefrontAiSemanticSearchService;

class StorefrontAiSemanticSearchController
{
    public function __construct(
        private readonly FeatureLimitService $featureLimitService,
        private readonly StorefrontAiSemanticSearchService $semanticSearch,
    ) {
    }

    public function search(Request $request): JsonResponse
    {
        $shop = $this->resolveShop($request);
        if (!$shop) {
            return response()->json(['message' => 'Boutique introuvable.'], 404);
        }

        $tenantId = $shop->tenant_id !== null ? (string) $shop->tenant_id : (string) $shop->id;
        $this->featureLimitService->assertFeatureEnabled($tenantId, 'ai.ecommerce.semantic_search');
        $this->featureLimitService->assertCanUseMonthlyFeature($tenantId, 'ai.ecommerce.semantic_search', 'la recherche sémantique IA');

        $cfg = $shop->ecommerce_storefront_config;
        if (!is_array($cfg)) {
            $cfg = [];
        }
        if (!(bool) ($cfg['ai_semantic_search_enabled'] ?? false)) {
            return response()->json(['message' => 'Recherche sémantique IA désactivée par la boutique.'], 403);
        }

        $validated = $request->validate([
            'query' => 'required|string|max:200',
        ]);
        $query = trim((string) $validated['query']);
        if ($query === '') {
            return response()->json(['product_ids' => []]);
        }

        $ids = $this->semanticSearch->searchProductIds((int) $shop->id, $query);
        $this->featureLimitService->recordFeatureUsage($tenantId, 'ai.ecommerce.semantic_search');

        return response()->json(['product_ids' => $ids]);
    }

    private function resolveShop(Request $request): ?Shop
    {
        $publicShop = $request->attributes->get('storefront_shop');
        if ($publicShop instanceof Shop) {
            return $publicShop;
        }
        $user = $request->user();
        if (!$user) {
            return null;
        }
        if ($user->shop_id) {
            $shop = Shop::find((int) $user->shop_id);
            if ($shop) {
                return $shop;
            }
        }
        if ($user->tenant_id) {
            $shop = Shop::where('tenant_id', (string) $user->tenant_id)->first();
            if ($shop) {
                return $shop;
            }
        }
        if (method_exists($user, 'isRoot') && $user->isRoot()) {
            $sid = $request->session()->get('current_storefront_shop_id');
            if ($sid) {
                return Shop::find((int) $sid);
            }
        }

        return null;
    }
}
