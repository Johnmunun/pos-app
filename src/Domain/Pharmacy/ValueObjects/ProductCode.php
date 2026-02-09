<?php

namespace Src\Domain\Pharmacy\ValueObjects;

use InvalidArgumentException;

class ProductCode
{
    private string $value;

    public function __construct(string $code)
    {
        if (!preg_match('/^[A-Z0-9]{6,12}$/', $code)) {
            throw new InvalidArgumentException('Product code must be 6-12 uppercase letters/numbers');
        }
        
        $this->value = $code;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}