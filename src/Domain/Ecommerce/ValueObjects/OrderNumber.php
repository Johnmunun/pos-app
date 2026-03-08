<?php

namespace Src\Domain\Ecommerce\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object : OrderNumber
 *
 * Numéro de commande unique et immutable.
 */
class OrderNumber
{
    private string $value;

    public function __construct(string $value)
    {
        if (empty(trim($value))) {
            throw new InvalidArgumentException('Order number cannot be empty');
        }

        if (strlen($value) > 50) {
            throw new InvalidArgumentException('Order number cannot exceed 50 characters');
        }

        $this->value = trim($value);
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

    public static function generate(): self
    {
        $prefix = 'ECOM-';
        $timestamp = date('Ymd');
        $random = strtoupper(substr(uniqid(), -6));
        return new self($prefix . $timestamp . '-' . $random);
    }
}
