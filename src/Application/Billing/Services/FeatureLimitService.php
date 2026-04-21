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

    private function limitReachedMessage(string $baseMessage): string
    {
        return $baseMessage . ' Vous avez atteint la limite du plan Trial. Passez a un plan superieur pour debloquer plus de fonctionnalites.';
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
            abort(403, $this->limitReachedMessage("Limite de produits atteinte pour votre plan ({$limit})."));
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

    public function assertCanUseMonthlyFeature(?string $tenantId, string $featureCode, ?string $label = null): void
    {
        if ($tenantId === null || $tenantId === '') {
            return;
        }

        $config = $this->repository->getTenantFeatureConfig((string) $tenantId, $featureCode);
        if (!($config['enabled'] ?? false)) {
            abort(403, 'Cette fonctionnalite n\'est pas incluse dans votre plan actuel.');
        }

        $limit = $config['limit'] ?? null;
        if ($limit === null) {
            return;
        }

        $used = $this->countMonthlyFeatureUsage((string) $tenantId, $featureCode);
        if ($used >= (int) $limit) {
            $name = $label ?? 'cette fonctionnalite';
            abort(403, $this->limitReachedMessage("Quota mensuel atteint pour {$name} ({$limit}/mois)."));
        }
    }

    public function recordFeatureUsage(?string $tenantId, string $featureCode, int $quantity = 1): void
    {
        if ($tenantId === null || $tenantId === '' || $quantity <= 0) {
            return;
        }

        if (!Schema::hasTable('tenant_feature_usage_events')) {
            return;
        }

        DB::table('tenant_feature_usage_events')->insert([
            'tenant_id' => (string) $tenantId,
            'feature_code' => $featureCode,
            'quantity' => $quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function countMonthlyFeatureUsage(string $tenantId, string $featureCode): int
    {
        if (!Schema::hasTable('tenant_feature_usage_events')) {
            return 0;
        }

        return (int) DB::table('tenant_feature_usage_events')
            ->where('tenant_id', (string) $tenantId)
            ->where('feature_code', $featureCode)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('quantity');
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
            abort(403, $this->limitReachedMessage("Limite d'utilisateurs atteinte pour votre plan ({$limit})."));
        }
    }

    public function assertCanCreateCategory(?string $tenantId): void
    {
        $this->assertTenantLimit($tenantId, 'categories.max', 'Limite de categories atteinte pour votre plan');
    }

    public function assertCanCreateSupplier(?string $tenantId): void
    {
        $this->assertTenantLimit($tenantId, 'suppliers.max', 'Limite de fournisseurs atteinte pour votre plan');
    }

    public function assertCanCreateCustomer(?string $tenantId): void
    {
        $this->assertTenantLimit($tenantId, 'customers.max', 'Limite de clients atteinte pour votre plan');
    }

    public function assertCanCreateDepot(?string $tenantId): void
    {
        $this->assertTenantLimit($tenantId, 'depots.max', 'Limite de depots atteinte pour votre plan');
    }

    private function assertTenantLimit(?string $tenantId, string $featureCode, string $message): void
    {
        if ($tenantId === null || $tenantId === '') {
            return;
        }

        $config = $this->repository->getTenantFeatureConfig((string) $tenantId, $featureCode);
        if (!($config['enabled'] ?? true)) {
            return;
        }

        $limit = $config['limit'] ?? null;
        if ($limit === null) {
            return;
        }

        $count = $this->countTenantResource((string) $tenantId, $featureCode);
        if ($count >= (int) $limit) {
            abort(403, $this->limitReachedMessage($message . " ({$limit})."));
        }
    }

    private function countTenantResource(string $tenantId, string $featureCode): int
    {
        $shopIds = collect();
        if (Schema::hasTable('shops')) {
            $shopIds = DB::table('shops')->where('tenant_id', $tenantId)->pluck('id');
        }

        return match ($featureCode) {
            'categories.max' => $this->countByShopTables($tenantId, $shopIds, [
                'gc_categories',
                'pharmacy_categories',
                'quincaillerie_categories',
            ]),
            'suppliers.max' => $this->countByShopTables($tenantId, $shopIds, [
                'gc_suppliers',
                'pharmacy_suppliers',
                'quincaillerie_suppliers',
            ]),
            'customers.max' => $this->countByShopTables($tenantId, $shopIds, [
                'customers',
                'pharmacy_customers',
                'quincaillerie_customers',
                'ecommerce_customers',
            ]),
            'depots.max' => Schema::hasTable('depots')
                ? (int) DB::table('depots')->where('tenant_id', $tenantId)->count()
                : 0,
            default => 0,
        };
    }

    private function countByShopTables(string $tenantId, \Illuminate\Support\Collection $shopIds, array $tables): int
    {
        $total = 0;
        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            if ($shopIds->isNotEmpty() && Schema::hasColumn($table, 'shop_id')) {
                $total += (int) DB::table($table)->whereIn('shop_id', $shopIds->all())->count();
                continue;
            }

            if (Schema::hasColumn($table, 'tenant_id')) {
                $total += (int) DB::table($table)->where('tenant_id', $tenantId)->count();
                continue;
            }

            if (Schema::hasColumn($table, 'shop_id')) {
                $total += (int) DB::table($table)->where('shop_id', $tenantId)->count();
            }
        }

        return $total;
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
                $this->limitReachedMessage(
                    "Le nombre de ventes mensuelles autorisé par votre plan est atteint ({$limit} pour le mois en cours). "
                    . 'Passez à un plan supérieur ou attendez le prochain mois pour continuer.'
                )
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
