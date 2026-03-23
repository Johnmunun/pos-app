<?php

namespace Src\Infrastructure\Billing\Persistence;

use Illuminate\Support\Facades\DB;
use Src\Domain\Billing\Repositories\BillingPlanRepositoryInterface;

class DbBillingPlanRepository implements BillingPlanRepositoryInterface
{
    public function listPlans(): array
    {
        return DB::table('billing_plans')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => (int) $plan->id,
                    'code' => (string) $plan->code,
                    'name' => (string) $plan->name,
                    'description' => $plan->description,
                    'monthly_price' => (float) $plan->monthly_price,
                    'annual_price' => $plan->annual_price !== null ? (float) $plan->annual_price : null,
                    'features' => is_string($plan->features) ? (json_decode($plan->features, true) ?: []) : [],
                    'is_active' => (bool) $plan->is_active,
                    'is_default' => (bool) $plan->is_default,
                    'sort_order' => (int) $plan->sort_order,
                ];
            })
            ->toArray();
    }

    public function listTenantSubscriptions(): array
    {
        return DB::table('tenant_plan_subscriptions as tps')
            ->join('tenants as t', 't.id', '=', 'tps.tenant_id')
            ->join('billing_plans as bp', 'bp.id', '=', 'tps.billing_plan_id')
            ->select([
                'tps.id',
                'tps.tenant_id',
                'tps.billing_plan_id',
                'tps.status',
                't.name as tenant_name',
                'bp.name as plan_name',
                'bp.code as plan_code',
            ])
            ->orderByDesc('tps.id')
            ->limit(200)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'tenant_id' => (int) $row->tenant_id,
                'tenant_name' => (string) $row->tenant_name,
                'billing_plan_id' => (int) $row->billing_plan_id,
                'plan_name' => (string) $row->plan_name,
                'plan_code' => (string) $row->plan_code,
                'status' => (string) $row->status,
            ])
            ->toArray();
    }

    public function updatePlan(int $planId, array $payload): void
    {
        DB::table('billing_plans')
            ->where('id', $planId)
            ->update([
                'name' => $payload['name'],
                'description' => $payload['description'] ?? null,
                'monthly_price' => $payload['monthly_price'] ?? 0,
                'annual_price' => $payload['annual_price'] ?? null,
                'features' => json_encode($payload['features'] ?? [], JSON_THROW_ON_ERROR),
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'updated_at' => now(),
            ]);
    }

    public function upsertTenantSubscription(string $tenantId, int $planId, string $status): void
    {
        DB::table('tenant_plan_subscriptions')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'status' => 'active',
            ],
            [
                'billing_plan_id' => $planId,
                'status' => $status,
                'starts_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function getTenantFeatureConfig(string $tenantId, string $featureCode): array
    {
        $subscription = DB::table('tenant_plan_subscriptions as tps')
            ->join('billing_plans as bp', 'bp.id', '=', 'tps.billing_plan_id')
            ->where('tps.tenant_id', $tenantId)
            ->where('tps.status', 'active')
            ->select(['bp.features'])
            ->orderByDesc('tps.id')
            ->first();

        if ($subscription === null) {
            $subscription = DB::table('billing_plans')
                ->where('is_default', true)
                ->select(['features'])
                ->first();
        }

        $features = [];
        if ($subscription && is_string($subscription->features)) {
            $features = json_decode($subscription->features, true) ?: [];
        }

        $base = $features[$featureCode] ?? ['enabled' => true, 'limit' => null];

        $override = DB::table('tenant_feature_overrides')
            ->where('tenant_id', $tenantId)
            ->where('feature_code', $featureCode)
            ->first();

        if ($override !== null) {
            if ($override->is_enabled !== null) {
                $base['enabled'] = (bool) $override->is_enabled;
            }
            if ($override->limit_value !== null) {
                $base['limit'] = (int) $override->limit_value;
            }
        }

        return [
            'enabled' => (bool) ($base['enabled'] ?? true),
            'limit' => array_key_exists('limit', $base) ? $base['limit'] : null,
        ];
    }

    public function upsertTenantFeatureOverride(string $tenantId, string $featureCode, ?bool $isEnabled, ?int $limitValue): void
    {
        DB::table('tenant_feature_overrides')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'feature_code' => $featureCode,
            ],
            [
                'is_enabled' => $isEnabled,
                'limit_value' => $limitValue,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function listTenantFeatureOverrides(): array
    {
        return DB::table('tenant_feature_overrides as tfo')
            ->join('tenants as t', 't.id', '=', 'tfo.tenant_id')
            ->select(['tfo.id', 'tfo.tenant_id', 'tfo.feature_code', 'tfo.is_enabled', 'tfo.limit_value', 't.name as tenant_name'])
            ->orderByDesc('tfo.id')
            ->limit(300)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'tenant_id' => (string) $row->tenant_id,
                'tenant_name' => (string) $row->tenant_name,
                'feature_code' => (string) $row->feature_code,
                'is_enabled' => $row->is_enabled !== null ? (bool) $row->is_enabled : null,
                'limit_value' => $row->limit_value !== null ? (int) $row->limit_value : null,
            ])
            ->toArray();
    }

    public function deleteTenantFeatureOverride(int $overrideId): void
    {
        DB::table('tenant_feature_overrides')
            ->where('id', $overrideId)
            ->delete();
    }
}
