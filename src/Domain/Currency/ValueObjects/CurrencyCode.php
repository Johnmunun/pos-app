<?php

namespace Src\Domain\Currency\ValueObjects;

/**
 * Value Object: CurrencyCode
 * 
 * Code ISO 4217 d'une devise (3 caractères)
 */
class CurrencyCode
{
    private string $value;

    public function __construct(string $code)
    {
        $code = strtoupper(trim($code));
        
        if (strlen($code) !== 3) {
            throw new \InvalidArgumentException('Currency code must be exactly 3 characters');
        }

        if (!preg_match('/^[A-Z]{3}$/', $code)) {
            throw new \InvalidArgumentException('Currency code must contain only uppercase letters');
        }

        $this->value = $code;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(CurrencyCode $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
