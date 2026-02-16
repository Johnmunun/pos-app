<?php

declare(strict_types=1);

namespace Src\Domain\Pharmacy\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object representing a batch/lot number for pharmaceutical products.
 */
final class BatchNumber
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        
        if ($trimmed === '') {
            throw new InvalidArgumentException('Le numéro de lot ne peut pas être vide.');
        }
        
        if (strlen($trimmed) > 50) {
            throw new InvalidArgumentException('Le numéro de lot ne peut pas dépasser 50 caractères.');
        }
        
        $this->value = $trimmed;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(BatchNumber $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
