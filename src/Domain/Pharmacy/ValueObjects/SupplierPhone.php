<?php

declare(strict_types=1);

namespace Src\Domain\Pharmacy\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object: SupplierPhone
 *
 * Représente le numéro de téléphone d'un fournisseur (optionnel mais validé si présent).
 */
final class SupplierPhone
{
    private ?string $value;

    public function __construct(?string $value)
    {
        if ($value !== null && $value !== '') {
            $value = trim($value);
            
            // Nettoyer le numéro : garder uniquement chiffres, +, espaces, tirets, parenthèses
            $cleaned = preg_replace('/[^\d+\-\s().]/', '', $value);
            
            if ($cleaned === null || $cleaned === '') {
                throw new InvalidArgumentException(
                    sprintf('Invalid phone number format: %s', $value)
                );
            }
            
            // Vérifier qu'il y a au moins quelques chiffres
            $digitsOnly = preg_replace('/\D/', '', $cleaned);
            if ($digitsOnly === null || strlen($digitsOnly) < 6) {
                throw new InvalidArgumentException(
                    sprintf('Phone number must contain at least 6 digits: %s', $value)
                );
            }
            
            $this->value = $cleaned;
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

    public function getDigitsOnly(): ?string
    {
        if ($this->value === null) {
            return null;
        }
        
        return preg_replace('/\D/', '', $this->value);
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
