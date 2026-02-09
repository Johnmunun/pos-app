<?php

namespace Src\Domain\Currency\ValueObjects;

/**
 * Value Object: CurrencyName
 * 
 * Nom d'une devise
 */
class CurrencyName
{
    private string $value;

    public function __construct(string $name)
    {
        $name = trim($name);
        
        if (empty($name)) {
            throw new \InvalidArgumentException('Currency name cannot be empty');
        }

        if (strlen($name) > 255) {
            throw new \InvalidArgumentException('Currency name cannot exceed 255 characters');
        }

        $this->value = $name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(CurrencyName $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
