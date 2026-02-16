<?php

namespace Src\Domain\Pharmacy\ValueObjects;

use InvalidArgumentException;

class ProductCode
{
    private string $value;

    public function __construct(string $code)
    {
        // Normaliser : trim + majuscules + suppression des caractères non autorisés
        $normalized = strtoupper(trim($code));
        $normalized = preg_replace('/[^A-Z0-9_.-]/', '', $normalized);

        // Règle business assouplie :
        // - longueur entre 4 et 12 caractères (conforme à la colonne VARCHAR(12))
        // - lettres/chiffres + caractères spéciaux simples: _ . -
        if ($normalized === null || !preg_match('/^[A-Z0-9_.-]{4,12}$/', $normalized)) {
            throw new InvalidArgumentException('Product code must be 4-12 characters (letters, numbers, _ . -).');
        }
        
        $this->value = $normalized;
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