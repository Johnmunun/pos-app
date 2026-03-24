<?php

namespace Src\Infrastructure\Billing\Persistence;

use Illuminate\Support\Facades\DB;
use Src\Domain\Billing\Repositories\BillingPlanRepositoryInterface;

class DbBillingPlanRepository implements BillingPlanRepositoryInterface
{
    private function applyPromotion(?string $promoType, $promoValue, ?string $promoStartsAt, ?string $promoEndsAt, float $amount): array
    {
        $now = now();
        $starts = $promoStartsAt ? now()->parse($promoStartsAt) : null;
        $ends = $promoEndsAt ? now()->parse($promoEndsAt) : null;
        $activeWindow = (!$starts || $now->greaterThanOrEqualTo($starts)) && (!$ends || $now->lessThanOrEqualTo($ends));
        $hasPromo = $promoType !== null && $promoValue !== null && $activeWindow;

        if (!$hasPromo) {
            return ['amount' => $amount, 'active' => false];
        }

        $value = (float) $promoValue;
        if ($promoType === 'percentage') {
            $discounted = $amount - (($amount * $value) / 100);
        } elseif ($promoType === 'fixed') {
            $discounted = $amount - $value;
        } else {
            $discounted = $amount;
        }

        return ['amount' => max(0.0, round($discounted, 2)), 'active' => true];
    }

    public function listPlans(): array
    {
        return DB::table('billing_plans')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function ($plan) {
                $monthly = (float) $plan->monthly_price;
                $annual = $plan->annual_price !== null ? (float) $plan->annual_price : null;
                $promoType = isset($plan->promo_type) ? $plan->promo_type : null;
                $promoValue = isset($plan->promo_value) ? $plan->promo_value : null;
                $promoStartsAt = isset($plan->promo_starts_at) ? (string) $plan->promo_starts_at : null;
                $promoEndsAt = isset($plan->promo_ends_at) ? (string) $plan->promo_ends_at : null;
                $monthlyPromo = $this->applyPromotion($promoType, $promoValue, $promoStartsAt, $promoEndsAt, $monthly);
                $annualPromo = $annual !== null
                    ? $this->applyPromotion($promoType, $promoValue, $promoStartsAt, $promoEndsAt, $annual)
                    : ['amount' => null, 'active' => $monthlyPromo['active']];

                return [
                    'id' => (int) $plan->id,
                    'code' => (string) $plan->code,
                    'name' => (string) $plan->name,
                    'description' => $plan->description,
                    'monthly_price' => $monthly,
                    'annual_price' => $annual,
                    'currency_code' => isset($plan->currency_code) && $plan->currency_code ? strtoupper((string) $plan->currency_code) : 'USD',
                    'promo_type' => $promoType,
                    'promo_value' => $promoValue !== null ? (float) $promoValue : null,
                    'promo_starts_at' => $promoStartsAt,
                    'promo_ends_at' => $promoEndsAt,
                    'promo_label' => isset($plan->promo_label) ? $plan->promo_label : null,
                    'monthly_price_effective' => $monthlyPromo['amount'],
                    'annual_price_effective' => $annualPromo['amount'],
                    'is_promo_active' => (bool) $monthlyPromo['active'],
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
                'currency_code' => strtoupper((string) ($payload['currency_code'] ?? 'USD')),
                'promo_type' => $payload['promo_type'] ?? null,
                'promo_value' => $payload['promo_value'] ?? null,
                'promo_starts_at' => $payload['promo_starts_at'] ?? null,
                'promo_ends_at' => $payload['promo_ends_at'] ?? null,
                'promo_label' => $payload['promo_label'] ?? null,
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
