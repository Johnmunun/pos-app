<?php

namespace Src\Domain\Finance\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object : marge bénéficiaire en pourcentage.
 * Marge (%) = (Bénéfice / Prix de vente total) × 100
 */
final class Margin
{
    private float $percentage;

    public function __construct(float $percentage)
    {
        if ($percentage < -100 || $percentage > 100) {
            throw new InvalidArgumentException('Margin percentage must be between -100 and 100');
        }
        $this->percentage = round($percentage, 2);
    }

    public static function fromProfitAndRevenue(float $profit, float $revenue): self
    {
        if ($revenue <= 0) {
            return new self(0.0);
        }
        $pct = ($profit / $revenue) * 100;
        return new self($pct);
    }

    public function getPercentage(): float
    {
        return $this->percentage;
    }

    public function __toString(): string
    {
        return number_format($this->percentage, 2, ',', ' ') . ' %';
    }
}
