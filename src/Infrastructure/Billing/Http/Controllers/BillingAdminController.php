<?php

namespace Src\Infrastructure\Billing\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\CurrencyConversionService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Src\Application\Billing\Services\BillingPlanService;
use Src\Infrastructure\Billing\Models\BillingPaymentTransaction;

class BillingAdminController
{
    private const FEATURE_CODES = ['products.max', 'users.max', 'api.payments', 'analytics.advanced'];
    private const PUBLIC_PLANS_CACHE_KEY = 'billing.public_plans.v1';

    public function __construct(
        private readonly BillingPlanService $billingPlanService,
        private readonly CurrencyConversionService $currencyConversionService,
    ) {
    }

    public function index(): Response
    {
        $data = $this->billingPlanService->dashboardData();
        $tenants = \App\Models\Tenant::query()
            ->orderBy('name')
            ->limit(300)
            ->get(['id', 'name'])
            ->map(fn ($t) => ['id' => (int) $t->id, 'name' => (string) $t->name])
            ->toArray();

        $compliance = $this->buildComplianceRows($tenants);
        $featureCatalog = (array) config('billing_features.catalog', []);

        return Inertia::render('Admin/BillingPlans', [
            'plans' => $data['plans'],
            'subscriptions' => $data['subscriptions'],
            'overrides' => $data['overrides'],
            'tenants' => $tenants,
            'compliance' => $compliance,
            'featureCatalog' => $featureCatalog,
        ]);
    }

    public function exportComplianceCsv(): StreamedResponse
    {
        $tenants = \App\Models\Tenant::query()
            ->orderBy('name')
            ->limit(300)
            ->get(['id', 'name'])
            ->map(fn ($t) => ['id' => (int) $t->id, 'name' => (string) $t->name])
            ->toArray();

        $rows = $this->buildComplianceRows($tenants);
        $filename = 'billing_compliance_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($rows): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fputcsv($out, ['tenant_id', 'tenant_name', 'products.max', 'users.max', 'api.payments', 'analytics.advanced']);
            foreach ($rows as $row) {
                $products = $row['features']['products.max']['enabled']
                    ? ((string) ($row['features']['products.max']['limit'] ?? 'illimite'))
                    : 'off';
                $users = $row['features']['users.max']['enabled']
                    ? ((string) ($row['features']['users.max']['limit'] ?? 'illimite'))
                    : 'off';

                fputcsv($out, [
                    $row['tenant_id'],
                    $row['tenant_name'],
                    $products,
                    $users,
                    $row['features']['api.payments']['enabled'] ? 'on' : 'off',
                    $row['features']['analytics.advanced']['enabled'] ? 'on' : 'off',
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function expiredSubscriptions(): Response
    {
        $plans = $this->billingPlanService->dashboardData()['plans'] ?? [];

        $activeRows = DB::table('tenant_plan_subscriptions as tps')
            ->join('tenants as t', 't.id', '=', 'tps.tenant_id')
            ->leftJoin('billing_plans as bp', 'bp.id', '=', 'tps.billing_plan_id')
            ->leftJoin('users as u', function ($join) {
                $join->on('u.tenant_id', '=', 'tps.tenant_id')
                    ->whereRaw('u.id = (select max(u2.id) from users u2 where u2.tenant_id = tps.tenant_id and u2.type != "ROOT")');
            })
            ->where('tps.status', 'active')
            ->where(function ($q) {
                $q->whereNull('tps.ends_at')
                    ->orWhere('tps.ends_at', '>=', now());
            })
            ->select([
                'tps.id as subscription_id',
                'tps.status as subscription_status',
                'tps.starts_at',
                'tps.ends_at',
                'tps.trial_ends_at',
                'tps.tenant_id',
                't.name as tenant_name',
                'bp.id as billing_plan_id',
                'bp.name as plan_name',
                'u.id as user_id',
                'u.name as user_name',
                'u.email as user_email',
            ])
            ->orderByDesc('tps.id')
            ->limit(300)
            ->get()
            ->map(static fn ($row) => [
                'user_id' => $row->user_id !== null ? (int) $row->user_id : 0,
                'user_name' => $row->user_name ? (string) $row->user_name : '—',
                'user_email' => $row->user_email ? (string) $row->user_email : '—',
                'tenant_id' => (int) $row->tenant_id,
                'tenant_name' => (string) $row->tenant_name,
                'subscription_id' => $row->subscription_id !== null ? (int) $row->subscription_id : null,
                'subscription_status' => $row->subscription_status ? (string) $row->subscription_status : null,
                'starts_at' => $row->starts_at ? (string) $row->starts_at : null,
                'expires_at' => $row->ends_at
                    ? (string) $row->ends_at
                    : ($row->trial_ends_at
                        ? (string) $row->trial_ends_at
                        : ($row->starts_at ? now()->parse($row->starts_at)->addMonth()->toDateTimeString() : null)),
                'billing_plan_id' => $row->billing_plan_id !== null ? (int) $row->billing_plan_id : null,
                'plan_name' => $row->plan_name ? (string) $row->plan_name : null,
            ])
            ->toArray();

        $rows = DB::table('tenant_plan_subscriptions as tps')
            ->join('tenants as t', 't.id', '=', 'tps.tenant_id')
            ->leftJoin('billing_plans as bp', 'bp.id', '=', 'tps.billing_plan_id')
            ->leftJoin('users as u', function ($join) {
                $join->on('u.tenant_id', '=', 'tps.tenant_id')
                    ->whereRaw('u.id = (select max(u2.id) from users u2 where u2.tenant_id = tps.tenant_id and u2.type != "ROOT")');
            })
            ->where(function ($q) {
                $q->where('tps.status', 'expired')
                    ->orWhere(function ($q2) {
                        $q2->where('tps.status', 'active')
                            ->whereNotNull('tps.ends_at')
                            ->where('tps.ends_at', '<', now());
                    })
                    ->orWhere(function ($q2) {
                        $q2->where('tps.status', 'active')
                            ->whereNull('tps.ends_at')
                            ->whereNotNull('tps.trial_ends_at')
                            ->where('tps.trial_ends_at', '<', now());
                    });
            })
            ->select([
                'tps.id as subscription_id',
                'tps.status as subscription_status',
                'tps.ends_at',
                'tps.trial_ends_at',
                'tps.tenant_id',
                't.name as tenant_name',
                'bp.id as billing_plan_id',
                'bp.name as plan_name',
                'u.id as user_id',
                'u.name as user_name',
                'u.email as user_email',
            ])
            ->orderByDesc('tps.id')
            ->limit(300)
            ->get()
            ->map(static fn ($row) => [
                'user_id' => $row->user_id !== null ? (int) $row->user_id : 0,
                'user_name' => $row->user_name ? (string) $row->user_name : '—',
                'user_email' => $row->user_email ? (string) $row->user_email : '—',
                'tenant_id' => (int) $row->tenant_id,
                'tenant_name' => (string) $row->tenant_name,
                'subscription_id' => $row->subscription_id !== null ? (int) $row->subscription_id : null,
                'subscription_status' => $row->subscription_status ? (string) $row->subscription_status : null,
                'expires_at' => $row->ends_at
                    ? (string) $row->ends_at
                    : ($row->trial_ends_at ? (string) $row->trial_ends_at : null),
                'billing_plan_id' => $row->billing_plan_id !== null ? (int) $row->billing_plan_id : null,
                'plan_name' => $row->plan_name ? (string) $row->plan_name : null,
            ])
            ->toArray();

        return Inertia::render('Admin/BillingExpiredSubscriptions', [
            'rows' => $rows,
            'activeRows' => $activeRows,
            'plans' => $plans,
        ]);
    }

    public function transactions(): Response
    {
        $rows = BillingPaymentTransaction::query()
            ->from('billing_payment_transactions as bpt')
            ->leftJoin('tenants as t', 't.id', '=', 'bpt.tenant_id')
            ->leftJoin('users as u', 'u.id', '=', 'bpt.user_id')
            ->leftJoin('billing_plans as bp', 'bp.id', '=', 'bpt.billing_plan_id')
            ->select([
                'bpt.id',
                'bpt.tenant_id',
                't.name as tenant_name',
                'bpt.user_id',
                'u.name as user_name',
                'u.email as user_email',
                'bpt.billing_plan_id',
                'bp.name as plan_name',
                'bp.code as plan_code',
                'bpt.payment_method',
                'bpt.amount',
                'bpt.currency_code',
                'bpt.status',
                'bpt.provider_reference',
                'bpt.paid_at',
                'bpt.created_at',
            ])
            ->orderByDesc('bpt.id')
            ->limit(300)
            ->get()
            ->map(static fn ($r) => [
                'id' => (int) $r->id,
                'tenant_id' => $r->tenant_id !== null ? (int) $r->tenant_id : null,
                'tenant_name' => $r->tenant_name ? (string) $r->tenant_name : null,
                'user_id' => $r->user_id !== null ? (int) $r->user_id : null,
                'user_name' => $r->user_name ? (string) $r->user_name : null,
                'user_email' => $r->user_email ? (string) $r->user_email : null,
                'billing_plan_id' => $r->billing_plan_id !== null ? (int) $r->billing_plan_id : null,
                'plan_name' => $r->plan_name ? (string) $r->plan_name : null,
                'plan_code' => $r->plan_code ? (string) $r->plan_code : null,
                'payment_method' => $r->payment_method ? (string) $r->payment_method : null,
                'amount' => $r->amount !== null ? (float) $r->amount : null,
                'currency_code' => $r->currency_code ? (string) $r->currency_code : null,
                'status' => $r->status ? (string) $r->status : null,
                'provider_reference' => $r->provider_reference ? (string) $r->provider_reference : null,
                'paid_at' => $r->paid_at ? (string) $r->paid_at : null,
                'created_at' => $r->created_at ? (string) $r->created_at : null,
            ])
            ->toArray();

        $subscriptions = DB::table('tenant_plan_subscriptions as tps')
            ->join('tenants as t', 't.id', '=', 'tps.tenant_id')
            ->join('billing_plans as bp', 'bp.id', '=', 'tps.billing_plan_id')
            ->where('tps.status', 'active')
            ->select([
                'tps.id',
                'tps.tenant_id',
                't.name as tenant_name',
                'tps.billing_plan_id',
                'bp.name as plan_name',
                'bp.code as plan_code',
                'tps.starts_at',
                'tps.ends_at',
                'tps.trial_ends_at',
            ])
            ->orderByRaw('tps.ends_at is null desc')
            ->orderBy('tps.ends_at')
            ->limit(300)
            ->get()
            ->map(static fn ($r) => [
                'id' => (int) $r->id,
                'tenant_id' => (int) $r->tenant_id,
                'tenant_name' => (string) $r->tenant_name,
                'billing_plan_id' => (int) $r->billing_plan_id,
                'plan_name' => (string) $r->plan_name,
                'plan_code' => (string) $r->plan_code,
                'starts_at' => $r->starts_at ? (string) $r->starts_at : null,
                'ends_at' => $r->ends_at ? (string) $r->ends_at : null,
                'trial_ends_at' => $r->trial_ends_at ? (string) $r->trial_ends_at : null,
            ])
            ->toArray();

        return Inertia::render('Admin/BillingTransactions', [
            'transactions' => $rows,
            'subscriptions' => $subscriptions,
        ]);
    }

    public function reactivateExpiredSubscription(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'billing_plan_id' => ['required', 'integer', 'exists:billing_plans,id'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
        ]);

        DB::transaction(function () use ($validated) {
            DB::table('tenant_plan_subscriptions')
                ->where('tenant_id', (string) $validated['tenant_id'])
                ->where('status', 'active')
                ->update([
                    'status' => 'replaced',
                    'updated_at' => now(),
                ]);

            DB::table('tenant_plan_subscriptions')->insert([
                'tenant_id' => (string) $validated['tenant_id'],
                'billing_plan_id' => (int) $validated['billing_plan_id'],
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addDays((int) $validated['duration_days']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $userUpdate = [
                'status' => 'active',
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('users', 'is_active')) {
                $userUpdate['is_active'] = true;
            }

            DB::table('users')
                ->where('tenant_id', (string) $validated['tenant_id'])
                ->where('status', 'inactive')
                ->update($userUpdate);
        });

        return back()->with('success', 'Abonnement reactive et comptes utilisateurs reactives.');
    }

    public function updatePlan(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'annual_price' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['required', 'string', 'size:3'],
            'promo_type' => ['nullable', 'in:percentage,fixed'],
            'promo_value' => ['nullable', 'numeric', 'min:0'],
            'promo_starts_at' => ['nullable', 'date'],
            'promo_ends_at' => ['nullable', 'date', 'after_or_equal:promo_starts_at'],
            'promo_label' => ['nullable', 'string', 'max:120'],
            'is_active' => ['required', 'boolean'],
            'features' => ['nullable', 'array'],
        ]);

        $validated['currency_code'] = strtoupper((string) $validated['currency_code']);

        $this->billingPlanService->savePlan($id, $validated);
        Cache::forget(self::PUBLIC_PLANS_CACHE_KEY);

        return back()->with('success', 'Plan mis a jour avec succes.');
    }

    public function applyTemplate(Request $request, int $id): RedirectResponse
    {
        $templateCodes = array_keys((array) config('billing_features.plan_templates', []));
        $validated = $request->validate([
            'template_code' => ['required', 'string', 'in:' . implode(',', $templateCodes)],
        ]);

        $plans = $this->billingPlanService->dashboardData()['plans'] ?? [];
        $plan = collect($plans)->firstWhere('id', $id);
        if (!$plan) {
            return back()->with('error', 'Plan introuvable.');
        }

        $template = config('billing_features.plan_templates.' . $validated['template_code']);
        if (!is_array($template)) {
            return back()->with('error', 'Template invalide.');
        }

        $this->billingPlanService->savePlan($id, [
            'name' => $plan['name'],
            'description' => $plan['description'] ?? '',
            'monthly_price' => $plan['monthly_price'] ?? 0,
            'annual_price' => $plan['annual_price'] ?? null,
            'currency_code' => $plan['currency_code'] ?? 'USD',
            'promo_type' => $plan['promo_type'] ?? null,
            'promo_value' => $plan['promo_value'] ?? null,
            'promo_starts_at' => $plan['promo_starts_at'] ?? null,
            'promo_ends_at' => $plan['promo_ends_at'] ?? null,
            'promo_label' => $plan['promo_label'] ?? null,
            'is_active' => $plan['is_active'] ?? true,
            'features' => $template,
        ]);
        Cache::forget(self::PUBLIC_PLANS_CACHE_KEY);

        return back()->with('success', 'Template applique avec succes.');
    }

    public function previewTemplate(Request $request, int $id): JsonResponse
    {
        $templateCodes = array_keys((array) config('billing_features.plan_templates', []));
        $validated = $request->validate([
            'template_code' => ['required', 'string', 'in:' . implode(',', $templateCodes)],
        ]);

        $plans = $this->billingPlanService->dashboardData()['plans'] ?? [];
        $plan = collect($plans)->firstWhere('id', $id);
        if (!$plan) {
            return response()->json(['message' => 'Plan introuvable.'], 404);
        }

        $current = (array) ($plan['features'] ?? []);
        $target = (array) config('billing_features.plan_templates.' . $validated['template_code'], []);
        $allCodes = array_values(array_unique(array_merge(array_keys($current), array_keys($target))));

        $changes = [];
        foreach ($allCodes as $code) {
            $from = $current[$code] ?? null;
            $to = $target[$code] ?? null;
            if ($from !== $to) {
                $changes[] = [
                    'code' => $code,
                    'from' => $from,
                    'to' => $to,
                ];
            }
        }

        return response()->json([
            'plan_id' => (int) $id,
            'template_code' => $validated['template_code'],
            'changes_count' => count($changes),
            'changes' => $changes,
        ]);
    }

    public function assignTenantPlan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'billing_plan_id' => ['required', 'integer', 'exists:billing_plans,id'],
            'status' => ['nullable', 'string', 'max:30'],
        ]);

        $this->billingPlanService->assignTenantPlan(
            (string) $validated['tenant_id'],
            (int) $validated['billing_plan_id'],
            (string) ($validated['status'] ?? 'active'),
            null
        );

        return back()->with('success', 'Abonnement tenant mis a jour.');
    }

    public function upsertOverride(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'feature_code' => ['required', 'string', 'max:120'],
            'is_enabled' => ['nullable', 'boolean'],
            'limit_value' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->billingPlanService->saveTenantFeatureOverride(
            (string) $validated['tenant_id'],
            (string) $validated['feature_code'],
            array_key_exists('is_enabled', $validated) ? (bool) $validated['is_enabled'] : null,
            array_key_exists('limit_value', $validated) && $validated['limit_value'] !== null ? (int) $validated['limit_value'] : null
        );

        return back()->with('success', 'Override tenant enregistre.');
    }

    public function deleteOverride(int $id): RedirectResponse
    {
        $this->billingPlanService->removeTenantFeatureOverride($id);
        return back()->with('success', 'Override supprime.');
    }

    public function plansApi(): JsonResponse
    {
        $plans = $this->billingPlanService->dashboardData()['plans'] ?? [];

        return response()->json([
            'data' => array_map(static function (array $plan): array {
                return [
                    'id' => $plan['id'],
                    'code' => $plan['code'],
                    'name' => $plan['name'],
                    'description' => $plan['description'],
                    'pricing' => [
                        'currency_code' => $plan['currency_code'] ?? 'USD',
                        'monthly' => $plan['monthly_price'],
                        'annual' => $plan['annual_price'],
                        'monthly_effective' => $plan['monthly_price_effective'] ?? $plan['monthly_price'],
                        'annual_effective' => $plan['annual_price_effective'] ?? $plan['annual_price'],
                    ],
                    'promotion' => [
                        'type' => $plan['promo_type'] ?? null,
                        'value' => $plan['promo_value'] ?? null,
                        'label' => $plan['promo_label'] ?? null,
                        'starts_at' => $plan['promo_starts_at'] ?? null,
                        'ends_at' => $plan['promo_ends_at'] ?? null,
                        'is_active' => (bool) ($plan['is_promo_active'] ?? false),
                    ],
                    'features' => $plan['features'] ?? [],
                    'is_active' => $plan['is_active'] ?? true,
                    'is_default' => $plan['is_default'] ?? false,
                ];
            }, $plans),
        ]);
    }

    public function fusionPayHealth(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'billing_plan_id' => ['required', 'integer', 'exists:billing_plans,id'],
        ]);

        $plan = DB::table('billing_plans')
            ->where('id', (int) $validated['billing_plan_id'])
            ->first(['id', 'name', 'monthly_price', 'currency_code']);

        if (!$plan) {
            return response()->json(['message' => 'Plan introuvable.'], 404);
        }

        $tenantId = (int) $validated['tenant_id'];
        $planCurrency = strtoupper((string) ($plan->currency_code ?? 'USD'));
        $fusionCurrency = strtoupper((string) config('fusionpay.payin_currency', 'CDF'));
        $minimumAmount = (float) config('fusionpay.minimum_amount', 200);
        $originalAmount = (float) ($plan->monthly_price ?? 0);

        $convertedAmount = $this->currencyConversionService->convert(
            $originalAmount,
            $planCurrency,
            $fusionCurrency,
            $tenantId
        );

        $rateMeta = $this->resolveRateMeta($tenantId, $planCurrency, $fusionCurrency);
        $finalAmount = (float) round((float) ($convertedAmount ?? $originalAmount), 0);

        $status = 'ok';
        $issues = [];
        if ($planCurrency !== $fusionCurrency && $convertedAmount === null) {
            $status = 'warning';
            $issues[] = sprintf('Aucun taux configure pour %s -> %s.', $planCurrency, $fusionCurrency);
        }
        if ($finalAmount <= $minimumAmount) {
            $status = 'warning';
            $issues[] = sprintf('Montant converti %.0f %s <= minimum requis %.0f %s.', $finalAmount, $fusionCurrency, $minimumAmount, $fusionCurrency);
        }

        return response()->json([
            'tenant_id' => $tenantId,
            'plan' => [
                'id' => (int) $plan->id,
                'name' => (string) $plan->name,
            ],
            'payin_currency' => $fusionCurrency,
            'minimum_amount' => $minimumAmount,
            'original_amount' => $originalAmount,
            'original_currency' => $planCurrency,
            'converted_amount' => $finalAmount,
            'rate' => $rateMeta,
            'status' => $status,
            'issues' => $issues,
        ]);
    }

    public function publicPlansApi(): JsonResponse
    {
        $activePlans = Cache::remember(self::PUBLIC_PLANS_CACHE_KEY, now()->addSeconds(60), function () {
            $plans = $this->billingPlanService->dashboardData()['plans'] ?? [];
            return array_values(array_filter($plans, static fn (array $plan): bool => (bool) ($plan['is_active'] ?? false)));
        });

        return response()->json([
            'data' => array_map(static function (array $plan): array {
                return [
                    'id' => $plan['id'],
                    'code' => $plan['code'],
                    'name' => $plan['name'],
                    'description' => $plan['description'],
                    'pricing' => [
                        'currency_code' => $plan['currency_code'] ?? 'USD',
                        'monthly' => $plan['monthly_price'],
                        'annual' => $plan['annual_price'],
                        'monthly_effective' => $plan['monthly_price_effective'] ?? $plan['monthly_price'],
                        'annual_effective' => $plan['annual_price_effective'] ?? $plan['annual_price'],
                    ],
                    'promotion' => [
                        'type' => $plan['promo_type'] ?? null,
                        'value' => $plan['promo_value'] ?? null,
                        'label' => $plan['promo_label'] ?? null,
                        'starts_at' => $plan['promo_starts_at'] ?? null,
                        'ends_at' => $plan['promo_ends_at'] ?? null,
                        'is_active' => (bool) ($plan['is_promo_active'] ?? false),
                    ],
                    'features' => $plan['features'] ?? [],
                    'is_default' => $plan['is_default'] ?? false,
                ];
            }, $activePlans),
        ]);
    }

    private function buildComplianceRows(array $tenants): array
    {
        return array_map(function (array $tenant): array {
            $tenantId = (string) $tenant['id'];
            $productsUsage = $this->countTenantProducts($tenantId);
            $usersUsage = $this->countTenantUsers($tenantId);

            $row = [
                'tenant_id' => $tenantId,
                'tenant_name' => (string) $tenant['name'],
                'features' => [],
                'usage' => [
                    'products' => $productsUsage,
                    'users' => $usersUsage,
                ],
            ];

            foreach (self::FEATURE_CODES as $featureCode) {
                $row['features'][$featureCode] = $this->billingPlanService
                    ->getTenantFeatureConfig($tenantId, $featureCode);
            }

            return $row;
        }, $tenants);
    }

    private function countTenantProducts(string $tenantId): int
    {
        $shopIds = \Illuminate\Support\Facades\DB::table('shops')
            ->where('tenant_id', $tenantId)
            ->pluck('id')
            ->toArray();

        $total = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('gc_products')) {
            $total += !empty($shopIds)
                ? \Illuminate\Support\Facades\DB::table('gc_products')->whereIn('shop_id', $shopIds)->count()
                : \Illuminate\Support\Facades\DB::table('gc_products')->where('shop_id', $tenantId)->count();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('pharmacy_products')) {
            $total += !empty($shopIds)
                ? \Illuminate\Support\Facades\DB::table('pharmacy_products')->whereIn('shop_id', $shopIds)->count()
                : \Illuminate\Support\Facades\DB::table('pharmacy_products')->where('shop_id', $tenantId)->count();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('quincaillerie_products')) {
            $total += !empty($shopIds)
                ? \Illuminate\Support\Facades\DB::table('quincaillerie_products')->whereIn('shop_id', $shopIds)->count()
                : \Illuminate\Support\Facades\DB::table('quincaillerie_products')->where('shop_id', $tenantId)->count();
        }

        return (int) $total;
    }

    private function countTenantUsers(string $tenantId): int
    {
        return (int) \Illuminate\Support\Facades\DB::table('users')
            ->where('tenant_id', $tenantId)
            ->where('type', '!=', 'ROOT')
            ->count();
    }

    private function resolveRateMeta(int $tenantId, string $fromCode, string $toCode): array
    {
        if ($fromCode === $toCode) {
            return [
                'value' => 1.0,
                'direction' => 'same_currency',
                'effective_date' => null,
            ];
        }

        $fromCurrency = DB::table('currencies')
            ->where('tenant_id', $tenantId)
            ->where('code', $fromCode)
            ->first(['id']);
        $toCurrency = DB::table('currencies')
            ->where('tenant_id', $tenantId)
            ->where('code', $toCode)
            ->first(['id']);

        if (!$fromCurrency || !$toCurrency) {
            return [
                'value' => null,
                'direction' => 'missing_currency',
                'effective_date' => null,
            ];
        }

        $direct = DB::table('exchange_rates')
            ->where('tenant_id', $tenantId)
            ->where('from_currency_id', (int) $fromCurrency->id)
            ->where('to_currency_id', (int) $toCurrency->id)
            ->orderByDesc('effective_date')
            ->first(['rate', 'effective_date']);

        if ($direct) {
            return [
                'value' => (float) $direct->rate,
                'direction' => 'direct',
                'effective_date' => (string) $direct->effective_date,
            ];
        }

        $inverse = DB::table('exchange_rates')
            ->where('tenant_id', $tenantId)
            ->where('from_currency_id', (int) $toCurrency->id)
            ->where('to_currency_id', (int) $fromCurrency->id)
            ->orderByDesc('effective_date')
            ->first(['rate', 'effective_date']);

        if ($inverse && (float) $inverse->rate > 0) {
            return [
                'value' => round(1 / (float) $inverse->rate, 8),
                'direction' => 'inverse',
                'effective_date' => (string) $inverse->effective_date,
            ];
        }

        return [
            'value' => null,
            'direction' => 'missing_rate',
            'effective_date' => null,
        ];
    }
}
