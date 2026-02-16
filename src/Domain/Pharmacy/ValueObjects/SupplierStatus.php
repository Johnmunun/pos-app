<?php

declare(strict_types=1);

namespace Src\Domain\Pharmacy\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object: SupplierStatus
 *
 * Représente le statut d'un fournisseur.
 */
final class SupplierStatus
{
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';

    private const VALID_STATUSES = [
        self::ACTIVE,
        self::INACTIVE,
    ];

    private string $value;

    public function __construct(string $value)
    {
        $normalized = strtolower(trim($value));

        if (!in_array($normalized, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid supplier status: %s. Valid statuses are: %s',
                    $value,
                    implode(', ', self::VALID_STATUSES)
                )
            );
        }

        $this->value = $normalized;
    }

    public static function active(): self
    {
        return new self(self::ACTIVE);
    }

    public static function inactive(): self
    {
        return new self(self::INACTIVE);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isActive(): bool
    {
        return $this->value === self::ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this->value === self::INACTIVE;
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
