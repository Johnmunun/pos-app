<?php

namespace Src\Application\Loyalty\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Src\Domain\Billing\Repositories\BillingPlanRepositoryInterface;

class LoyaltyLimitService
{
    public function __construct(
        private readonly BillingPlanRepositoryInterface $repository
    ) {
    }

    public function assertLoyaltyEnabled(?string $tenantId): void
    {
        if ($tenantId === null || $tenantId === '') {
            return;
        }

        $config = $this->repository->getTenantFeatureConfig($tenantId, 'loyalty.enabled');
        if (!($config['enabled'] ?? false)) {
            abort(403, 'Le programme de fidélité n\'est pas inclus dans votre plan actuel.');
        }
    }

    public function assertCanEnrollCustomer(?string $tenantId, ?string $excludingAccountId = null): void
    {
        if ($tenantId === null || $tenantId === '') {
            return;
        }

        $this->assertLoyaltyEnabled($tenantId);

        $config = $this->repository->getTenantFeatureConfig($tenantId, 'loyalty.accounts.max');
        if (!($config['enabled'] ?? true)) {
            abort(403, 'Les cartes fidélité ne sont pas disponibles pour votre plan.');
        }

        $limit = $config['limit'] ?? null;
        if ($limit === null) {
            return;
        }

        $count = $this->countActiveAccounts($tenantId, $excludingAccountId);
        if ($count >= (int) $limit) {
            abort(403, "Votre plan limite les cartes fidélité à {$limit} client(s). Veuillez mettre à niveau votre abonnement.");
        }
    }

    public function countActiveAccounts(string $tenantId, ?string $excludingAccountId = null): int
    {
        if (!Schema::hasTable('loyalty_accounts')) {
            return 0;
        }

        $q = DB::table('loyalty_accounts')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active');

        if ($excludingAccountId !== null) {
            $q->where('id', '!=', $excludingAccountId);
        }

        return (int) $q->count();
    }
}
