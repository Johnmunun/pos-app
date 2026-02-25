<?php

namespace Src\Domain\Finance\Services;

use Src\Domain\Finance\ValueObjects\Margin;
use Src\Shared\ValueObjects\Money;

/**
 * Domain Service : calcul du bénéfice et de la marge.
 * Bénéfice = (Prix de vente - Prix d'achat moyen) × Quantité vendue
 * Marge (%) = (Bénéfice / Prix de vente total) × 100
 * Pas de requêtes : reçoit des montants déjà agrégés ou calculés en amont (snapshots / lectures indexées).
 */
final class ProfitCalculatorService
{
    public function calculateProfit(float $totalRevenue, float $totalCost): float
    {
        return max(0, round($totalRevenue - $totalCost, 2));
    }

    public function calculateMargin(float $profit, float $revenue): Margin
    {
        return Margin::fromProfitAndRevenue($profit, $revenue);
    }

    /** Bénéfice pour une ligne : (prix_vente - coût_unit_moyen) * qty */
    public function lineProfit(float $unitSalePrice, float $unitCost, int $quantity): float
    {
        $revenue = $unitSalePrice * $quantity;
        $cost = $unitCost * $quantity;
        return $this->calculateProfit($revenue, $cost);
    }
}
