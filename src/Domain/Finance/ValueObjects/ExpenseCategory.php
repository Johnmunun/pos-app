<?php

namespace Src\Domain\Finance\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object : catégorie de dépense (achat stock, transport, salaire, charge fixe…).
 */
final class ExpenseCategory
{
    public const STOCK_PURCHASE = 'stock_purchase';
    public const TRANSPORT = 'transport';
    public const SALARY = 'salary';
    public const FIXED_CHARGE = 'fixed_charge';
    public const UTILITIES = 'utilities';
    public const MAINTENANCE = 'maintenance';
    public const OTHER = 'other';

    private string $value;

    public function __construct(string $value)
    {
        $allowed = self::all();
        if (!in_array($value, $allowed, true)) {
            throw new InvalidArgumentException("Invalid expense category: {$value}");
        }
        $this->value = $value;
    }

    public static function all(): array
    {
        return [
            self::STOCK_PURCHASE,
            self::TRANSPORT,
            self::SALARY,
            self::FIXED_CHARGE,
            self::UTILITIES,
            self::MAINTENANCE,
            self::OTHER,
        ];
    }

    public static function labels(): array
    {
        return [
            self::STOCK_PURCHASE => 'Achat stock',
            self::TRANSPORT => 'Transport',
            self::SALARY => 'Salaire',
            self::FIXED_CHARGE => 'Charge fixe',
            self::UTILITIES => 'Fournitures',
            self::MAINTENANCE => 'Maintenance',
            self::OTHER => 'Autre',
        ];
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return self::labels()[$this->value] ?? $this->value;
    }
}
