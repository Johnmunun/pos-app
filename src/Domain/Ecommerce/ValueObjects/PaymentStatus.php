<?php

namespace Src\Domain\Ecommerce\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object : PaymentStatus
 *
 * Statut de paiement d'une commande.
 */
class PaymentStatus
{
    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const FAILED = 'failed';
    public const REFUNDED = 'refunded';

    private string $value;

    public function __construct(string $value)
    {
        $validStatuses = [
            self::PENDING,
            self::PAID,
            self::FAILED,
            self::REFUNDED,
        ];

        if (!in_array($value, $validStatuses, true)) {
            throw new InvalidArgumentException("Invalid payment status: {$value}");
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

    public function isPaid(): bool
    {
        return $this->value === self::PAID;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
