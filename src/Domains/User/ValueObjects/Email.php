<?php

namespace Domains\User\ValueObjects;

/**
 * Value Object Email
 *
 * Représente l'adresse email d'un utilisateur.
 * Valide et immuable.
 *
 * Auto-validée au construction.
 */
final class Email
{
    private readonly string $value;

    public function __construct(string $value)
    {
        // Nettoyer et normaliser
        $value = trim($value);
        $value = strtolower($value);

        // Valider le format
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                'Email must be a valid email address'
            );
        }

        // Vérifier la longueur (RFC 5321)
        if (strlen($value) > 254) {
            throw new \InvalidArgumentException(
                'Email must not exceed 254 characters'
            );
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
