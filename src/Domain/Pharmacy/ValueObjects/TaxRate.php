<?php

declare(strict_types=1);

namespace Src\Domain\Pharmacy\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object: TaxRate
 *
 * Représente un taux de taxe (TVA ou autre).
 */
final class TaxRate
{
    private float $value;

    public function __construct(float $value)
    {
        if ($value < 0 || $value > 100) {
            throw new InvalidArgumentException(
                sprintf('Le taux de taxe doit être entre 0 et 100. Reçu: %s', $value)
            );
        }

        $this->value = round($value, 2);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function standard(): self
    {
        return new self(16); // TVA standard RDC
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getDecimal(): float
    {
        return $this->value / 100;
    }

    public function isZero(): bool
    {
        return $this->value === 0.0;
    }

    /**
     * Calcule le montant TTC à partir d'un montant HT.
     */
    public function calculateTaxIncluded(float $amountExcludingTax): float
    {
        return round($amountExcludingTax * (1 + $this->getDecimal()), 2);
    }

    /**
     * Calcule le montant de taxe.
     */
    public function calculateTaxAmount(float $amountExcludingTax): float
    {
        return round($amountExcludingTax * $this->getDecimal(), 2);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return sprintf('%.2f%%', $this->value);
    }
}
