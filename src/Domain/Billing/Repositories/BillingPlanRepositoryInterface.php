<?php

namespace Src\Domain\Billing\Repositories;

interface BillingPlanRepositoryInterface
{
    public function listPlans(): array;

    public function listTenantSubscriptions(): array;

    public function updatePlan(int $planId, array $payload): void;

    public function upsertTenantSubscription(string $tenantId, int $planId, string $status, ?string $endsAt = null): void;

    public function getTenantFeatureConfig(string $tenantId, string $featureCode): array;

    public function upsertTenantFeatureOverride(string $tenantId, string $featureCode, ?bool $isEnabled, ?int $limitValue): void;

    public function listTenantFeatureOverrides(): array;

    public function deleteTenantFeatureOverride(int $overrideId): void;
}
