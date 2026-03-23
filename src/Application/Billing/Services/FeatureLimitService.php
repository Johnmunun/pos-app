<?php

namespace Src\Application\Billing\Services;

use Illuminate\Support\Facades\DB;
use Src\Domain\Billing\Repositories\BillingPlanRepositoryInterface;

class FeatureLimitService
{
    public function __construct(
        private readonly BillingPlanRepositoryInterface $repository
    ) {
    }

    public function assertCanCreateProduct(?string $tenantId): void
    {
        if ($tenantId === null) {
            return;
        }

        $config = $this->repository->getTenantFeatureConfig($tenantId, 'products.max');
        if (!$config['enabled']) {
            abort(403, 'Cette fonctionnalite est desactivee pour votre plan.');
        }

        $limit = $config['limit'];
        if ($limit === null) {
            return;
        }

        $count = 0;
        $shops = collect();
        if (DB::getSchemaBuilder()->hasTable('shops')) {
            $shops = DB::table('shops')->where('tenant_id', $tenantId)->pluck('id');
        }

        if (DB::getSchemaBuilder()->hasTable('gc_products')) {
            if ($shops->isNotEmpty()) {
                $count += DB::table('gc_products')->whereIn('shop_id', $shops)->count();
            } else {
                $count += DB::table('gc_products')->where('shop_id', $tenantId)->count();
            }
        }
        if (DB::getSchemaBuilder()->hasTable('quincaillerie_products')) {
            if ($shops->isNotEmpty()) {
                $count += DB::table('quincaillerie_products')->whereIn('shop_id', $shops)->count();
            } else {
                $count += DB::table('quincaillerie_products')->where('shop_id', $tenantId)->count();
            }
        }
        if (DB::getSchemaBuilder()->hasTable('pharmacy_products')) {
            if ($shops->isNotEmpty()) {
                $count += DB::table('pharmacy_products')->whereIn('shop_id', $shops)->count();
            } else {
                $count += DB::table('pharmacy_products')->where('shop_id', $tenantId)->count();
            }
        }

        if ($count >= (int) $limit) {
            abort(403, "Limite de produits atteinte pour votre plan ({$limit}).");
        }
    }

    public function assertFeatureEnabled(?string $tenantId, string $featureCode): void
    {
        if ($tenantId === null) {
            return;
        }

        $config = $this->repository->getTenantFeatureConfig($tenantId, $featureCode);
        if (!$config['enabled']) {
            abort(403, 'Cette fonctionnalite n\'est pas incluse dans votre plan actuel.');
        }
    }

    public function isFeatureEnabled(?string $tenantId, string $featureCode): bool
    {
        if ($tenantId === null) {
            return true;
        }

        $config = $this->repository->getTenantFeatureConfig($tenantId, $featureCode);
        return (bool) ($config['enabled'] ?? true);
    }

    public function assertCanCreateUser(?string $tenantId): void
    {
        if ($tenantId === null) {
            return;
        }

        $config = $this->repository->getTenantFeatureConfig($tenantId, 'users.max');
        if (!$config['enabled']) {
            abort(403, 'La gestion des utilisateurs est desactivee pour votre plan.');
        }

        $limit = $config['limit'];
        if ($limit === null) {
            return;
        }

        $count = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->where('type', '!=', 'ROOT')
            ->count();

        if ($count >= (int) $limit) {
            abort(403, "Limite d'utilisateurs atteinte pour votre plan ({$limit}).");
        }
    }
}
