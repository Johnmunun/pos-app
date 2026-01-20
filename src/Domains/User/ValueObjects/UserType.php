<?php

namespace Domains\User\ValueObjects;

/**
 * Value Object UserType
 *
 * Représente le type d'utilisateur dans le système.
 *
 * Types:
 * - ROOT: Propriétaire/administrateur de l'application (contrôle tout)
 * - TENANT_ADMIN: Administrateur d'un tenant (contrôle son tenant)
 * - MERCHANT: Responsable magasin
 * - SELLER: Vendeur/caissier
 * - STAFF: Personnel support
 *
 * ⚠️ IMPORTANT: Les permissions précises sont dans le domain AccessControl
 * Ce value object ne fait que catégoriser les utilisateurs.
 */
final class UserType
{
    public const ROOT = 'ROOT';
    public const TENANT_ADMIN = 'TENANT_ADMIN';
    public const MERCHANT = 'MERCHANT';
    public const SELLER = 'SELLER';
    public const STAFF = 'STAFF';

    private readonly string $value;

    /**
     * Liste des types valides
     */
    private static array $validTypes = [
        self::ROOT,
        self::TENANT_ADMIN,
        self::MERCHANT,
        self::SELLER,
        self::STAFF,
    ];

    /**
     * Constructeur
     *
     * @param string $value Le type d'utilisateur
     * @throws \InvalidArgumentException Si le type n'est pas valide
     */
    public function __construct(string $value)
    {
        $value = strtoupper(trim($value));

        if (!in_array($value, self::$validTypes, true)) {
            throw new \InvalidArgumentException(
                "UserType '{$value}' is not valid. Valid types: " .
                implode(', ', self::$validTypes)
            );
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(UserType $other): bool
    {
        return $this->value === $other->value;
    }

    public function isRoot(): bool
    {
        return $this->value === self::ROOT;
    }

    public function isTenantAdmin(): bool
    {
        return $this->value === self::TENANT_ADMIN;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Obtenir tous les types valides
     */
    public static function getValidTypes(): array
    {
        return self::$validTypes;
    }
}
