<?php

namespace Src\Infrastructure\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Src\Application\Finance\UseCases\Expense\ListExpensesUseCase;
use Src\Application\Finance\UseCases\Profit\GenerateProfitReportUseCase;
use Src\Domain\Finance\Repositories\DebtRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class FinanceExportController extends Controller
{
    public function __construct(
        private ListExpensesUseCase $listExpensesUseCase,
        private GenerateProfitReportUseCase $generateProfitReportUseCase,
        private DebtRepositoryInterface $debtRepository
    ) {
    }

    private function getTenantId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $tenantId = $user->tenant_id ?? $user->shop_id ?? null;
        if ($tenantId === null) {
            abort(403, 'Tenant ID not found.');
        }
        return (string) $tenantId;
    }

    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = null;
        $depotId = $request->session()->get('current_depot_id');
        if ($depotId && $user->tenant_id && \Illuminate\Support\Facades\Schema::hasTable('shops')) {
            $shopByDepot = \App\Models\Shop::where('depot_id', $depotId)->where('tenant_id', $user->tenant_id)->first();
            if ($shopByDepot) {
                $shopId = (string) $shopByDepot->id;
            }
        }
        if ($shopId === null) {
            $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        }
        if ($shopId === null) {
            abort(403, 'Shop ID not found.');
        }
        return (string) $shopId;
    }

    public function expensesPdf(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $filters = [
            'shop_id' => $request->input('shop_id'),
            'category' => $request->input('category'),
            'status' => $request->input('status'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ];

        $result = $this->listExpensesUseCase->execute($tenantId, 1000, 1, array_filter($filters));

        $items = array_map(static function ($e) {
            return [
                'id' => $e->getId(),
                'amount' => $e->getAmount()->getAmount(),
                'currency' => $e->getAmount()->getCurrency(),
                'category' => $e->getCategory()->getValue(),
                'description' => $e->getDescription(),
                'status' => $e->getStatus(),
                'created_at' => $e->getCreatedAt()->format('Y-m-d H:i'),
                'paid_at' => $e->getPaidAt()?->format('Y-m-d H:i'),
            ];
        }, $result['items']);

        $totalAmount = array_sum(array_column($items, 'amount'));

        $view = view('finance.exports.expenses', [
            'filters' => $filters,
            'items' => $items,
            'summary' => [
                'count' => count($items),
                'total_amount' => $totalAmount,
                'currency' => $items[0]['currency'] ?? 'CDF',
            ],
        ])->render();

        $pdf = Pdf::loadHTML($view)->setPaper('a4', 'portrait');
        $fileName = 'depenses-' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($fileName);
    }

    public function dashboardPdf(Request $request): Response
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
            array_filter(['shop_id' => $shopId, 'from' => $fromInput, 'to' => $toInput])
        );
        $totalExpenses = array_sum(array_map(static fn ($e) => $e->getAmount()->getAmount(), $expenseResult['items']));

        $profitReport = $this->generateProfitReportUseCase->execute($shopId, $from, $to);

        $debtsClient = $this->debtRepository->findByShop($shopId, 'client', null);
        $debtsSupplier = $this->debtRepository->findByShop($shopId, 'supplier', null);
        $totalDebtsClient = array_sum(array_map(static fn ($d) => $d->getBalance()->getAmount(), $debtsClient));
        $totalDebtsSupplier = array_sum(array_map(static fn ($d) => $d->getBalance()->getAmount(), $debtsSupplier));

        $view = view('finance.exports.dashboard', [
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
            ],
            'filters' => [
                'from' => $fromInput,
                'to' => $toInput,
            ],
        ])->render();

        $pdf = Pdf::loadHTML($view)->setPaper('a4', 'portrait');
        $fileName = 'dashboard-finance-' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($fileName);
    }
}

