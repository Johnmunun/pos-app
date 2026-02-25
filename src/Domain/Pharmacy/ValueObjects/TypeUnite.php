<?php

namespace Src\Domain\Pharmacy\ValueObjects;

use InvalidArgumentException;

/**
 * Type d'unité de vente : PLAQUETTE (divisible), BOITE, FLACON (non divisibles), etc.
 */
class TypeUnite
{
    public const PLAQUETTE = 'PLAQUETTE';
    public const BOITE = 'BOITE';
    public const FLACON = 'FLACON';
    public const TUBE = 'TUBE';
    public const SACHET = 'SACHET';
    public const UNITE = 'UNITE'; // générique

    private const VALID_TYPES = [
        self::PLAQUETTE,
        self::BOITE,
        self::FLACON,
        self::TUBE,
        self::SACHET,
        self::UNITE,
    ];

    /** Types pour lesquels la vente fractionnée est autorisée par défaut */
    private const DIVISIBLE_BY_DEFAULT = [
        self::PLAQUETTE,
        self::SACHET,
        self::UNITE,
    ];

    private string $value;

    public function __construct(string $type)
    {
        $type = strtoupper(trim($type));
        if (!in_array($type, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException(
                'Type d\'unité invalide. Valides : ' . implode(', ', self::VALID_TYPES)
            );
        }
        $this->value = $type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isDivisibleByDefault(): bool
    {
        return in_array($this->value, self::DIVISIBLE_BY_DEFAULT, true);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public static function getAllTypes(): array
    {
        return self::VALID_TYPES;
    }
}
