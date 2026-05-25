<?php

namespace Src\Application\Billing\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Src\Domain\Billing\Repositories\BillingPlanRepositoryInterface;

/**
 * Quotas promotions : produits catalogue (remise %) et campagnes e-commerce actives.
 */
class PromotionLimitService
{
    private const ADVANCED_CAMPAIGN_TYPES = ['buy_x_get_y', 'free_shipping'];

    public function __construct(
        private readonly BillingPlanRepositoryInterface $repository
    ) {
    }

    public function assertCanSetProductPromotion(?string $tenantId, float $newDiscountPercent, ?string $productId = null): void
    {
        if ($tenantId === null || $tenantId === '' || $newDiscountPercent <= 0) {
            return;
        }

        $config = $this->repository->getTenantFeatureConfig($tenantId, 'promotions.products.max');
        if (!($config['enabled'] ?? true)) {
            abort(403, 'Les promotions produits ne sont pas incluses dans votre plan actuel.');
        }

        $limit = $config['limit'] ?? null;
        if ($limit === null) {
            return;
        }

        if ($productId !== null && $this->productHasActivePromotion($productId)) {
            return;
        }

        $count = $this->countActivePromotionalProducts($tenantId, $productId);
        if ($count >= (int) $limit) {
            abort(403, $this->buildLimitMessage(
                "Votre plan actuel limite les promotions à {$limit} produit(s).",
                (int) $limit
            ));
        }
    }

    public function assertCanActivateEcommercePromotion(
        ?string $tenantId,
        bool $willBeActive,
        string $type,
        ?string $excludingPromotionId = null
    ): void {
        if ($tenantId === null || $tenantId === '' || !$willBeActive) {
            return;
        }

        $this->assertFeatureEnabled($tenantId, 'ecommerce.promotions');
        $this->assertAdvancedCampaignTypeAllowed($tenantId, $type);

        $config = $this->repository->getTenantFeatureConfig($tenantId, 'ecommerce.promotions');
        $limit = $config['limit'] ?? null;
        if ($limit === null) {
            return;
        }

        if ($excludingPromotionId !== null && $this->ecommercePromotionIsCurrentlyActive($excludingPromotionId)) {
            return;
        }

        $count = $this->countActiveEcommercePromotions($tenantId, $excludingPromotionId);
        if ($count >= (int) $limit) {
            abort(403, $this->buildLimitMessage(
                "Votre plan actuel limite les promotions actives à {$limit}.",
                (int) $limit
            ));
        }
    }

    public function assertAdvancedCampaignTypeAllowed(?string $tenantId, string $type): void
    {
        if ($tenantId === null || $tenantId === '' || !in_array($type, self::ADVANCED_CAMPAIGN_TYPES, true)) {
            return;
        }

        $config = $this->repository->getTenantFeatureConfig($tenantId, 'promotions.advanced');
        if (!($config['enabled'] ?? false)) {
            abort(403, 'Les promotions avancées (ex. achat X offert Y, livraison gratuite) ne sont pas incluses dans votre plan actuel. Veuillez mettre à niveau votre abonnement.');
        }
    }

    private function assertFeatureEnabled(string $tenantId, string $featureCode): void
    {
        $config = $this->repository->getTenantFeatureConfig($tenantId, $featureCode);
        if (!($config['enabled'] ?? false)) {
            abort(403, 'Cette fonctionnalité n\'est pas incluse dans votre plan actuel.');
        }
    }

    /**
     * @return array{
     *   products: array{used: int, limit: int|null, enabled: bool, at_limit: bool},
     *   campaigns: array{used: int, limit: int|null, enabled: bool, at_limit: bool},
     *   advanced_enabled: bool,
     *   plan_name: string|null
     * }
     */
    public function getQuotaSummary(?string $tenantId): array
    {
        if ($tenantId === null || $tenantId === '') {
            return $this->emptyQuotaSummary();
        }

        $productsConfig = $this->repository->getTenantFeatureConfig($tenantId, 'promotions.products.max');
        $campaignsConfig = $this->repository->getTenantFeatureConfig($tenantId, 'ecommerce.promotions');
        $advancedConfig = $this->repository->getTenantFeatureConfig($tenantId, 'promotions.advanced');

        $productsUsed = $this->countActivePromotionalProducts($tenantId);
        $campaignsUsed = $this->countActiveEcommercePromotions($tenantId);

        $productsLimit = ($productsConfig['enabled'] ?? true) ? ($productsConfig['limit'] ?? null) : 0;
        $campaignsLimit = ($campaignsConfig['enabled'] ?? false) ? ($campaignsConfig['limit'] ?? null) : 0;

        return [
            'products' => [
                'used' => $productsUsed,
                'limit' => $productsLimit === null ? null : (int) $productsLimit,
                'enabled' => (bool) ($productsConfig['enabled'] ?? true),
                'at_limit' => $productsLimit !== null && $productsUsed >= (int) $productsLimit,
            ],
            'campaigns' => [
                'used' => $campaignsUsed,
                'limit' => $campaignsLimit === null ? null : (int) $campaignsLimit,
                'enabled' => (bool) ($campaignsConfig['enabled'] ?? false),
                'at_limit' => $campaignsLimit !== null && $campaignsUsed >= (int) $campaignsLimit,
            ],
            'advanced_enabled' => (bool) ($advancedConfig['enabled'] ?? false),
            'plan_name' => $this->resolveActivePlanName($tenantId),
        ];
    }

    public function productHasActivePromotion(string $productId): bool
    {
        if (!Schema::hasTable('gc_products')) {
            return false;
        }

        $row = DB::table('gc_products')
            ->where('id', $productId)
            ->where('discount_percent', '>', 0)
            ->where('status', 'active')
            ->exists();

        return $row;
    }

    public function ecommercePromotionIsCurrentlyActive(string $promotionId): bool
    {
        if (!Schema::hasTable('ecommerce_promotions')) {
            return false;
        }

        $now = now();
        $q = DB::table('ecommerce_promotions')
            ->where('id', $promotionId)
            ->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now);

        if (Schema::hasColumn('ecommerce_promotions', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        return $q->exists();
    }

    public function countActivePromotionalProducts(string $tenantId, ?string $excludingProductId = null): int
    {
        $shopIds = $this->shopIdsForTenant($tenantId);
        $total = 0;

        if (Schema::hasTable('gc_products') && Schema::hasColumn('gc_products', 'discount_percent')) {
            $q = DB::table('gc_products')
                ->where('discount_percent', '>', 0)
                ->where('status', 'active');
            $this->applyShopScope($q, 'gc_products', $shopIds, $tenantId);
            if ($excludingProductId !== null && $excludingProductId !== '') {
                $q->where('id', '!=', $excludingProductId);
            }
            $total += (int) $q->count();
        }

        foreach (['pharmacy_products', 'quincaillerie_products'] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'discount_percent')) {
                continue;
            }
            $q = DB::table($table)
                ->where('discount_percent', '>', 0);
            if (Schema::hasColumn($table, 'status')) {
                $q->where('status', 'active');
            } elseif (Schema::hasColumn($table, 'is_active')) {
                $q->where('is_active', true);
            }
            $this->applyShopScope($q, $table, $shopIds, $tenantId);
            if ($excludingProductId !== null && $excludingProductId !== '') {
                $q->where('id', '!=', $excludingProductId);
            }
            $total += (int) $q->count();
        }

        return $total;
    }

    public function countActiveEcommercePromotions(string $tenantId, ?string $excludingPromotionId = null): int
    {
        if (!Schema::hasTable('ecommerce_promotions')) {
            return 0;
        }

        $shopIds = $this->shopIdsForTenant($tenantId);
        $now = now();

        $q = DB::table('ecommerce_promotions')
            ->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now);

        if (Schema::hasColumn('ecommerce_promotions', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        if ($shopIds->isNotEmpty()) {
            $q->whereIn('shop_id', $shopIds->all());
        }

        if ($excludingPromotionId !== null && $excludingPromotionId !== '') {
            $q->where('id', '!=', $excludingPromotionId);
        }

        return (int) $q->count();
    }

    private function shopIdsForTenant(string $tenantId): Collection
    {
        if (!Schema::hasTable('shops')) {
            return collect();
        }

        return DB::table('shops')->where('tenant_id', $tenantId)->pluck('id');
    }

    private function applyShopScope($query, string $table, Collection $shopIds, string $tenantId): void
    {
        if ($shopIds->isNotEmpty() && Schema::hasColumn($table, 'shop_id')) {
            $query->whereIn('shop_id', $shopIds->all());

            return;
        }

        if (Schema::hasColumn($table, 'shop_id')) {
            $query->where('shop_id', $tenantId);
        }
    }

    private function resolveActivePlanName(string $tenantId): ?string
    {
        if (!Schema::hasTable('tenant_plan_subscriptions') || !Schema::hasTable('billing_plans')) {
            return null;
        }

        $row = DB::table('tenant_plan_subscriptions as tps')
            ->join('billing_plans as bp', 'bp.id', '=', 'tps.billing_plan_id')
            ->where('tps.tenant_id', $tenantId)
            ->where('tps.status', 'active')
            ->orderByDesc('tps.id')
            ->value('bp.name');

        return $row !== null ? (string) $row : null;
    }

    private function buildLimitMessage(string $detail, int $limit): string
    {
        return $detail.' Veuillez mettre à niveau votre abonnement pour en ajouter davantage.';
    }

    /**
     * @return array{
     *   products: array{used: int, limit: int|null, enabled: bool, at_limit: bool},
     *   campaigns: array{used: int, limit: int|null, enabled: bool, at_limit: bool},
     *   advanced_enabled: bool,
     *   plan_name: string|null
     * }
     */
    private function emptyQuotaSummary(): array
    {
        return [
            'products' => ['used' => 0, 'limit' => null, 'enabled' => true, 'at_limit' => false],
            'campaigns' => ['used' => 0, 'limit' => null, 'enabled' => true, 'at_limit' => false],
            'advanced_enabled' => true,
            'plan_name' => null,
        ];
    }
}
