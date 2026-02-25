<?php

namespace Src\Shared\ValueObjects;

use InvalidArgumentException;

/**
 * Quantité pouvant être entière ou décimale (ex. 0.5 pour demi-plaquette).
 * Utilisé en vente (demi-plaquette, quart de boîte) et en stock (décimal si besoin).
 */
class Quantity
{
    private float $value;

    public function __construct(int|float $value)
    {
        $v = is_int($value) ? (float) $value : $value;
        if ($v < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative');
        }
        $this->value = round($v, 4);
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function add(Quantity $other): self
    {
        return new self($this->value + $other->value);
    }

    public function subtract(Quantity $other): self
    {
        $result = $this->value - $other->value;
        if ($result < -0.0001) {
            throw new InvalidArgumentException('Result cannot be negative');
        }
        return new self($result < 0 ? 0 : $result);
    }

    public function multiply(int|float $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException('Multiplier cannot be negative');
        }
        return new self($this->value * (float) $multiplier);
    }

    public function isZero(): bool
    {
        return $this->value < 0.0001;
    }

    public function isGreaterThan(Quantity $other): bool
    {
        return $this->value > $other->value;
    }

    public function isGreaterThanOrEqual(Quantity $other): bool
    {
        return $this->value >= $other->value - 0.0001;
    }

    public function equals(self $other): bool
    {
        return abs($this->value - $other->value) < 0.0001;
    }

    public function __toString(): string
    {
        return $this->value === (float) (int) $this->value
            ? (string) (int) $this->value
            : (string) $this->value;
    }
}