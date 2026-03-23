<?php

namespace Src\Infrastructure\Billing\Http\Controllers;

use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Billing\Services\BillingPlanService;

class BillingAdminController
{
    private const FEATURE_CODES = ['products.max', 'users.max', 'api.payments', 'analytics.advanced'];

    public function __construct(
        private readonly BillingPlanService $billingPlanService
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

        return Inertia::render('Admin/BillingPlans', [
            'plans' => $data['plans'],
            'subscriptions' => $data['subscriptions'],
            'overrides' => $data['overrides'],
            'tenants' => $tenants,
            'compliance' => $compliance,
        ]);
    }

    public function exportComplianceCsv(): HttpResponse
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

    public function updatePlan(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'annual_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'features' => ['nullable', 'array'],
        ]);

        $this->billingPlanService->savePlan($id, $validated);

        return back()->with('success', 'Plan mis a jour avec succes.');
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
            (string) ($validated['status'] ?? 'active')
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
}
