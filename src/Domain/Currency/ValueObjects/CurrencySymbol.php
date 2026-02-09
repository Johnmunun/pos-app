<?php

namespace Src\Domain\Currency\ValueObjects;

/**
 * Value Object: CurrencySymbol
 * 
 * Symbole d'une devise (ex: $, €, FCFA)
 */
class CurrencySymbol
{
    private string $value;

    public function __construct(string $symbol)
    {
        $symbol = trim($symbol);
        
        if (empty($symbol)) {
            throw new \InvalidArgumentException('Currency symbol cannot be empty');
        }

        if (strlen($symbol) > 10) {
            throw new \InvalidArgumentException('Currency symbol cannot exceed 10 characters');
        }

        $this->value = $symbol;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(CurrencySymbol $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
