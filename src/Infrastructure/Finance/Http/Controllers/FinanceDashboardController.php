<?php

namespace Src\Infrastructure\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Finance\UseCases\Expense\ListExpensesUseCase;
use Src\Application\Finance\UseCases\Profit\GenerateProfitReportUseCase;
use Src\Domain\Finance\Repositories\DebtRepositoryInterface;
use Src\Domain\Finance\Repositories\ExpenseRepositoryInterface;

/**
 * Contrôleur Finance - Dashboard.
 * Agrège des données via Use Cases / Repositories ; pas de logique métier.
 */
class FinanceDashboardController
{
    public function __construct(
        private ListExpensesUseCase $listExpensesUseCase,
        private GenerateProfitReportUseCase $generateProfitReportUseCase,
        private ExpenseRepositoryInterface $expenseRepository,
        private DebtRepositoryInterface $debtRepository
    ) {}

    private function getTenantId(Request $request): string
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }
        $tenantId = $user->tenant_id ?? $user->shop_id ?? null;
        if (!$tenantId) {
            abort(403, 'Tenant ID not found.');
        }
        return (string) $tenantId;
    }

    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }
        $depotId = $request->session()->get('current_depot_id');
        if ($depotId && $user->tenant_id && \Illuminate\Support\Facades\Schema::hasTable('shops')) {
            $shop = \App\Models\Shop::where('depot_id', $depotId)->where('tenant_id', $user->tenant_id)->first();
            if ($shop) {
                return (string) $shop->id;
            }
        }
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        if (!$shopId) {
            abort(403, 'Shop ID not found.');
        }
        return (string) $shopId;
    }

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);
        $shopId = $this->getShopId($request);
        $fromInput = $request->input('from');
        $toInput = $request->input('to');

        $from = $fromInput ? \DateTimeImmutable::createFromFormat('Y-m-d', $fromInput) : null;
        $to = $toInput ? \DateTimeImmutable::createFromFormat('Y-m-d', $toInput) : null;

        $expenseResult = $this->listExpensesUseCase->execute(
            $tenantId,
            1000,
            1,
            array_filter(['shop_id' => $shopId, 'from' => $from?->format('Y-m-d'), 'to' => $to?->format('Y-m-d')])
        );
        $totalExpenses = array_sum(array_map(fn ($e) => $e->getAmount()->getAmount(), $expenseResult['items']));

        $profitReport = $this->generateProfitReportUseCase->execute($shopId, $from, $to);

        $debtsClient = $this->debtRepository->findByShop($shopId, 'client', null);
        $debtsSupplier = $this->debtRepository->findByShop($shopId, 'supplier', null);
        $totalDebtsClient = array_sum(array_map(fn ($d) => $d->getBalance()->getAmount(), $debtsClient));
        $totalDebtsSupplier = array_sum(array_map(fn ($d) => $d->getBalance()->getAmount(), $debtsSupplier));

        $byProduct = $profitReport->getByProduct();
        usort(
            $byProduct,
            fn (array $a, array $b): int => $b['profit'] <=> $a['profit']
        );
        $topProducts = array_slice($byProduct, 0, 10);

        $lowMarginProducts = array_filter(
            $profitReport->getByProduct(),
            fn (array $p): bool => $p['margin_percent'] < 10 && $p['revenue'] > 0
        );
        usort(
            $lowMarginProducts,
            fn (array $a, array $b): int => $a['margin_percent'] <=> $b['margin_percent']
        );
        $lowMarginProducts = array_slice($lowMarginProducts, 0, 10);

        return Inertia::render('Finance/Dashboard/Index', [
            'dashboard' => [
                'total_revenue' => $profitReport->getTotalRevenue(),
                'total_expenses' => $totalExpenses,
                'gross_profit' => $profitReport->getGrossProfit(),
                'margin_percent' => $profitReport->getMargin()->getPercentage(),
                'currency' => $profitReport->getCurrency(),
                'period_from' => $profitReport->getPeriodFrom()?->format('Y-m-d'),
                'period_to' => $profitReport->getPeriodTo()?->format('Y-m-d'),
                'debts_client_total' => $totalDebtsClient,
                'debts_supplier_total' => $totalDebtsSupplier,
                'top_10_profitable_products' => $topProducts,
                'low_margin_products' => array_values($lowMarginProducts),
            ],
            'filters' => [
                'from' => $fromInput,
                'to' => $toInput,
            ],
        ]);
    }
}
