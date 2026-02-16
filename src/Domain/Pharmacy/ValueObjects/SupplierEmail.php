<?php

declare(strict_types=1);

namespace Src\Domain\Pharmacy\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object: SupplierEmail
 *
 * Représente l'email d'un fournisseur (optionnel mais validé si présent).
 */
final class SupplierEmail
{
    private ?string $value;

    public function __construct(?string $value)
    {
        if ($value !== null && $value !== '') {
            $value = trim($value);
            
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException(
                    sprintf('Invalid email format: %s', $value)
                );
            }
            
            $this->value = strtolower($value);
        } else {
            $this->value = null;
        }
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function isEmpty(): bool
    {
        return $this->value === null;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value ?? '';
    }
}
