<?php

namespace Src\Domain\Pharmacy\ValueObjects;

use InvalidArgumentException;

class Dosage
{
    private string $value;

    public function __construct(string $dosage)
    {
        if (empty($dosage) || strlen($dosage) > 50) {
            throw new InvalidArgumentException('Dosage must be between 1 and 50 characters');
        }

        $this->value = trim($dosage);
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