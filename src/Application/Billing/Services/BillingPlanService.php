<?php

namespace Src\Application\Billing\Services;

use Src\Domain\Billing\Repositories\BillingPlanRepositoryInterface;

class BillingPlanService
{
    public function __construct(
        private readonly BillingPlanRepositoryInterface $repository
    ) {
    }

    public function dashboardData(): array
    {
        return [
            'plans' => $this->repository->listPlans(),
            'subscriptions' => $this->repository->listTenantSubscriptions(),
            'overrides' => $this->repository->listTenantFeatureOverrides(),
        ];
    }

    public function savePlan(int $planId, array $payload): void
    {
        $this->repository->updatePlan($planId, $payload);
    }

    public function assignTenantPlan(string $tenantId, int $planId, string $status = 'active'): void
    {
        $this->repository->upsertTenantSubscription($tenantId, $planId, $status);
    }

    public function saveTenantFeatureOverride(string $tenantId, string $featureCode, ?bool $isEnabled, ?int $limitValue): void
    {
        $this->repository->upsertTenantFeatureOverride($tenantId, $featureCode, $isEnabled, $limitValue);
    }

    public function removeTenantFeatureOverride(int $overrideId): void
    {
        $this->repository->deleteTenantFeatureOverride($overrideId);
    }

    public function getTenantFeatureConfig(string $tenantId, string $featureCode): array
    {
        return $this->repository->getTenantFeatureConfig($tenantId, $featureCode);
    }
}
