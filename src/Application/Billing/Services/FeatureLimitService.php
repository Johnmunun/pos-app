<?php

namespace Src\Application\Billing\Services;

use App\Services\AppNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Src\Domain\Billing\Repositories\BillingPlanRepositoryInterface;

class FeatureLimitService
{
    public function __construct(
        private readonly BillingPlanRepositoryInterface $repository,
        private readonly AppNotificationService $appNotificationService
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

    public function getTenantFeatureConfig(string $tenantId, string $featureCode): array
    {
        return $this->repository->getTenantFeatureConfig($tenantId, $featureCode);
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

    /**
     * Verifie le plafond ventes du mois (POS commerce, pharmacie/quincaillerie, commandes e-commerce non annulees).
     */
    public function assertCanRecordSale(?string $tenantId): void
    {
        if ($tenantId === null || $tenantId === '') {
            return;
        }

        $config = $this->repository->getTenantFeatureConfig($tenantId, 'sales.monthly.max');
        if (!($config['enabled'] ?? true)) {
            return;
        }

        $limit = $config['limit'];
        if ($limit === null) {
            return;
        }

        $count = $this->countMonthlySalesForTenant((string) $tenantId);
        if ($count >= (int) $limit) {
            $this->appNotificationService->notifySalesMonthlyLimitReached(
                (int) $tenantId,
                (int) $limit,
                $count
            );
            abort(
                403,
                "Le nombre de ventes mensuelles autorisé par votre plan est atteint ({$limit} pour le mois en cours). "
                . 'Passez à un plan supérieur ou attendez le prochain mois pour continuer.'
            );
        }
    }

    /**
     * Compte les ventes du mois civil en cours pour le tenant (tous modules).
     */
    public function countMonthlySalesForTenant(string $tenantId): int
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $shopIds = collect();
        if (Schema::hasTable('shops')) {
            $shopIds = DB::table('shops')->where('tenant_id', $tenantId)->pluck('id');
        }

        $total = 0;

        if (Schema::hasTable('gc_sales')) {
            $q = DB::table('gc_sales')
                ->whereRaw('UPPER(TRIM(status)) = ?', ['COMPLETED'])
                ->whereBetween('created_at', [$start, $end]);
            if ($shopIds->isNotEmpty()) {
                $q->whereIn('shop_id', $shopIds->all());
            } else {
                $q->where('shop_id', $tenantId);
            }
            $total += (int) $q->count();
        }

        if (Schema::hasTable('pharmacy_sales')) {
            $q = DB::table('pharmacy_sales')
                ->where('status', 'COMPLETED')
                ->where(function ($sub) use ($start, $end) {
                    $sub->whereBetween('completed_at', [$start, $end])
                        ->orWhere(function ($sub2) use ($start, $end) {
                            $sub2->whereNull('completed_at')
                                ->whereBetween('updated_at', [$start, $end]);
                        });
                });
            if ($shopIds->isNotEmpty()) {
                $q->whereIn('shop_id', $shopIds->map(fn ($id) => (string) $id)->all());
            } else {
                $q->where('shop_id', (string) $tenantId);
            }
            $total += (int) $q->count();
        }

        if (Schema::hasTable('ecommerce_orders')) {
            $q = DB::table('ecommerce_orders')
                ->where('status', '!=', 'cancelled')
                ->whereBetween('created_at', [$start, $end]);
            if (Schema::hasColumn('ecommerce_orders', 'deleted_at')) {
                $q->whereNull('deleted_at');
            }
            if ($shopIds->isNotEmpty()) {
                $q->whereIn('shop_id', $shopIds->all());
            } else {
                $q->where('shop_id', $tenantId);
            }
            $total += (int) $q->count();
        }

        return $total;
    }
}
