<?php

namespace Src\Shared\ValueObjects;

use InvalidArgumentException;

class Quantity
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative');
        }

        $this->value = $value;
    }

    public function getValue(): int
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
        if ($result < 0) {
            throw new InvalidArgumentException('Result cannot be negative');
        }

        return new self($result);
    }

    public function multiply(int $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException('Multiplier cannot be negative');
        }

        return new self($this->value * $multiplier);
    }

    public function isZero(): bool
    {
        return $this->value === 0;
    }

    public function isGreaterThan(Quantity $other): bool
    {
        return $this->value > $other->value;
    }

    public function isGreaterThanOrEqual(Quantity $other): bool
    {
        return $this->value >= $other->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}