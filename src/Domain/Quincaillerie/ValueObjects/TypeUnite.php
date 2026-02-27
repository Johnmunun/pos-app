<?php

namespace Src\Domain\Quincaillerie\ValueObjects;

use InvalidArgumentException;

/**
 * Type d'unité de vente pour la quincaillerie : PIECE, LOT, METRE, KG, etc.
 */
final class TypeUnite
{
    public const PIECE = 'PIECE';
    public const LOT = 'LOT';
    public const METRE = 'METRE';
    public const KG = 'KG';
    public const LITRE = 'LITRE';
    public const BOITE = 'BOITE';
    public const CARTON = 'CARTON';
    public const UNITE = 'UNITE';

    private const VALID_TYPES = [
        self::PIECE,
        self::LOT,
        self::METRE,
        self::KG,
        self::LITRE,
        self::BOITE,
        self::CARTON,
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

    public static function getAllTypes(): array
    {
        return self::VALID_TYPES;
    }
}
