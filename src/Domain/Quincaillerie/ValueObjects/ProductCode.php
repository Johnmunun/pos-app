<?php

namespace Src\Domain\Quincaillerie\ValueObjects;

use InvalidArgumentException;

final class ProductCode
{
    private string $value;

    public function __construct(string $code)
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            throw new InvalidArgumentException('Product code cannot be empty');
        }
        if (strlen($code) > 50) {
            throw new InvalidArgumentException('Product code cannot exceed 50 characters');
        }
        $this->value = $code;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
