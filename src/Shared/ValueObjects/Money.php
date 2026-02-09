<?php

namespace Src\Shared\ValueObjects;

use InvalidArgumentException;

class Money
{
    private float $amount;
    private string $currency;

    public function __construct(float $amount, string $currency = 'USD')
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }

        if (!in_array($currency, ['USD', 'EUR', 'CDF'])) {
            throw new InvalidArgumentException('Unsupported currency');
        }

        $this->amount = round($amount, 2);
        $this->currency = $currency;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function add(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot add different currencies');
        }

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot subtract different currencies');
        }

        $result = $this->amount - $other->amount;
        if ($result < 0) {
            throw new InvalidArgumentException('Result cannot be negative');
        }

        return new self($result, $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException('Multiplier cannot be negative');
        }

        return new self($this->amount * $multiplier, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot compare different currencies');
        }

        return $this->amount >= $other->amount;
    }

    public function format(): string
    {
        $symbol = match($this->currency) {
            'USD' => '$',
            'EUR' => '€',
            'CDF' => 'FC',
            default => $this->currency
        };

        return $symbol . number_format($this->amount, 2);
    }

    public function __toString(): string
    {
        return $this->format();
    }
}