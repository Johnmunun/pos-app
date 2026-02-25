<?php

namespace Src\Application\Finance\UseCases\Profit;

use Src\Domain\Finance\Entities\FinancialReport;
use Src\Domain\Finance\Repositories\ProfitDataProviderInterface;
use Src\Domain\Finance\Services\ProfitCalculatorService;
use Src\Domain\Finance\ValueObjects\Margin;

/**
 * Génère un rapport de bénéfice par période.
 * Données fournies par ProfitDataProvider (requête indexée unique en Infrastructure).
 */
final class GenerateProfitReportUseCase
{
    public function __construct(
        private ProfitDataProviderInterface $profitDataProvider,
        private ProfitCalculatorService $profitCalculator
    ) {}

    public function execute(string $shopId, ?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null): FinancialReport
    {
        $lines = $this->profitDataProvider->getCompletedSaleLinesForPeriod($shopId, $from, $to);
        $byProduct = [];
        $totalRevenue = 0.0;
        $totalCost = 0.0;

        foreach ($lines as $line) {
            $productId = $line['product_id'];
            $qty = (int) $line['quantity'];
            $unitPrice = (float) $line['unit_price'];
            $unitCost = (float) ($line['unit_cost'] ?? 0);
            $revenue = $unitPrice * $qty;
            $cost = $unitCost * $qty;
            $profit = $this->profitCalculator->lineProfit($unitPrice, $unitCost, $qty);

            $totalRevenue += $revenue;
            $totalCost += $cost;

            if (!isset($byProduct[$productId])) {
                $byProduct[$productId] = [
                    'product_id' => $productId,
                    'product_name' => $line['product_name'] ?? '—',
                    'quantity_sold' => 0,
                    'revenue' => 0.0,
                    'cost' => 0.0,
                    'profit' => 0.0,
                ];
            }
            $byProduct[$productId]['quantity_sold'] += $qty;
            $byProduct[$productId]['revenue'] += $revenue;
            $byProduct[$productId]['cost'] += $cost;
            $byProduct[$productId]['profit'] += $profit;
        }

        foreach ($byProduct as $id => &$row) {
            $row['margin_percent'] = $row['revenue'] > 0
                ? Margin::fromProfitAndRevenue($row['profit'], $row['revenue'])->getPercentage()
                : 0.0;
        }
        unset($row);

        $grossProfit = $this->profitCalculator->calculateProfit($totalRevenue, $totalCost);
        $margin = $this->profitCalculator->calculateMargin($grossProfit, $totalRevenue);

        return new FinancialReport(
            $totalRevenue,
            $totalCost,
            $grossProfit,
            $margin,
            'CDF',
            array_values($byProduct),
            $from,
            $to
        );
    }
}
