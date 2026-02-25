<?php

namespace Src\Domain\Finance\Entities;

use Src\Domain\Finance\ValueObjects\Margin;
use Src\Shared\ValueObjects\Money;

/**
 * Entité de rapport financier (bénéfice par produit / période / global).
 * Données calculées par ProfitCalculatorService et Use Cases, pas de logique métier ici.
 */
class FinancialReport
{
    private float $totalRevenue;
    private float $totalCost;
    private float $grossProfit;
    private Margin $margin;
    private string $currency;
    /** @var array<int, array{product_id: string, product_name: string, quantity_sold: int, revenue: float, cost: float, profit: float, margin_percent: float}> */
    private array $byProduct;
    private ?\DateTimeImmutable $periodFrom;
    private ?\DateTimeImmutable $periodTo;

    public function __construct(
        float $totalRevenue,
        float $totalCost,
        float $grossProfit,
        Margin $margin,
        string $currency,
        array $byProduct = [],
        ?\DateTimeImmutable $periodFrom = null,
        ?\DateTimeImmutable $periodTo = null
    ) {
        $this->totalRevenue = $totalRevenue;
        $this->totalCost = $totalCost;
        $this->grossProfit = $grossProfit;
        $this->margin = $margin;
        $this->currency = $currency;
        $this->byProduct = $byProduct;
        $this->periodFrom = $periodFrom;
        $this->periodTo = $periodTo;
    }

    public function getTotalRevenue(): float { return $this->totalRevenue; }
    public function getTotalCost(): float { return $this->totalCost; }
    public function getGrossProfit(): float { return $this->grossProfit; }
    public function getMargin(): Margin { return $this->margin; }
    public function getCurrency(): string { return $this->currency; }
    /** @return array<int, array{product_id: string, product_name: string, quantity_sold: int, revenue: float, cost: float, profit: float, margin_percent: float}> */
    public function getByProduct(): array { return $this->byProduct; }
    public function getPeriodFrom(): ?\DateTimeImmutable { return $this->periodFrom; }
    public function getPeriodTo(): ?\DateTimeImmutable { return $this->periodTo; }
}
