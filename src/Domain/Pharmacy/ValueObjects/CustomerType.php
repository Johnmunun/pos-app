<?php

declare(strict_types=1);

namespace Src\Domain\Pharmacy\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object: CustomerType
 *
 * Représente le type de client (particulier ou entreprise).
 */
final class CustomerType
{
    public const INDIVIDUAL = 'individual';
    public const COMPANY = 'company';

    private const VALID_TYPES = [
        self::INDIVIDUAL,
        self::COMPANY,
    ];

    private const LABELS = [
        self::INDIVIDUAL => 'Particulier',
        self::COMPANY => 'Entreprise',
    ];

    private string $value;

    public function __construct(string $value)
    {
        $normalized = strtolower(trim($value));

        if (!in_array($normalized, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Type de client invalide: %s. Types valides: %s',
                    $value,
                    implode(', ', self::VALID_TYPES)
                )
            );
        }

        $this->value = $normalized;
    }

    public static function individual(): self
    {
        return new self(self::INDIVIDUAL);
    }

    public static function company(): self
    {
        return new self(self::COMPANY);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return self::LABELS[$this->value];
    }

    public function isIndividual(): bool
    {
        return $this->value === self::INDIVIDUAL;
    }

    public function isCompany(): bool
    {
        return $this->value === self::COMPANY;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @return array<string, string>
     */
    public static function getAll(): array
    {
        return self::LABELS;
    }
}
