<?php

namespace Src\Domain\Ecommerce\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object : OrderStatus
 *
 * Statut d'une commande ecommerce.
 */
class OrderStatus
{
    public const PENDING = 'pending';
    public const CONFIRMED = 'confirmed';
    public const PROCESSING = 'processing';
    public const SHIPPED = 'shipped';
    public const DELIVERED = 'delivered';
    public const CANCELLED = 'cancelled';

    private string $value;

    public function __construct(string $value)
    {
        $validStatuses = [
            self::PENDING,
            self::CONFIRMED,
            self::PROCESSING,
            self::SHIPPED,
            self::DELIVERED,
            self::CANCELLED,
        ];

        if (!in_array($value, $validStatuses, true)) {
            throw new InvalidArgumentException("Invalid order status: {$value}");
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $transitions = [
            self::PENDING => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::SHIPPED, self::CANCELLED],
            self::SHIPPED => [self::DELIVERED],
            self::DELIVERED => [],
            self::CANCELLED => [],
        ];

        return in_array($newStatus, $transitions[$this->value] ?? [], true);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
