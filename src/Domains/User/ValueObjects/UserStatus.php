<?php

namespace Domains\User\ValueObjects;

/**
 * Value Object UserStatus
 *
 * Représente le statut d'un utilisateur dans le système.
 * Aucune dépendance vers Laravel ou l'infrastructure.
 *
 * Statuts possibles:
 * - pending: Compte en attente de validation
 * - active: Compte actif et fonctionnel
 * - blocked: Compte bloqué (accès refusé)
 * - suspended: Compte suspendu temporairement
 *
 * @package Src\Domains\User\ValueObjects
 */
final class UserStatus
{
    public const PENDING = 'pending';
    public const ACTIVE = 'active';
    public const BLOCKED = 'blocked';
    public const SUSPENDED = 'suspended';

    private readonly string $value;

    /**
     * Liste des statuts valides
     */
    private static array $validStatuses = [
        self::PENDING,
        self::ACTIVE,
        self::BLOCKED,
        self::SUSPENDED,
    ];

    /**
     * Constructeur
     *
     * @param string $value Le statut utilisateur
     * @throws \InvalidArgumentException Si le statut n'est pas valide
     */
    public function __construct(string $value)
    {
        $value = strtolower(trim($value));

        if (!in_array($value, self::$validStatuses, true)) {
            throw new \InvalidArgumentException(
                "UserStatus '{$value}' is not valid. Valid statuses: " .
                implode(', ', self::$validStatuses)
            );
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(UserStatus $other): bool
    {
        return $this->value === $other->value;
    }

    public function isPending(): bool
    {
        return $this->value === self::PENDING;
    }

    public function isActive(): bool
    {
        return $this->value === self::ACTIVE;
    }

    public function isBlocked(): bool
    {
        return $this->value === self::BLOCKED;
    }

    public function isSuspended(): bool
    {
        return $this->value === self::SUSPENDED;
    }

    /**
     * Vérifie si l'utilisateur peut se connecter
     *
     * @return bool
     */
    public function canLogin(): bool
    {
        return $this->isActive();
    }

    /**
     * Vérifie si l'utilisateur est bloqué ou suspendu
     *
     * @return bool
     */
    public function isRestricted(): bool
    {
        return $this->isBlocked() || $this->isSuspended();
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Obtenir tous les statuts valides
     */
    public static function getValidStatuses(): array
    {
        return self::$validStatuses;
    }

    /**
     * Factory methods
     */
    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    public static function active(): self
    {
        return new self(self::ACTIVE);
    }

    public static function blocked(): self
    {
        return new self(self::BLOCKED);
    }

    public static function suspended(): self
    {
        return new self(self::SUSPENDED);
    }
}
