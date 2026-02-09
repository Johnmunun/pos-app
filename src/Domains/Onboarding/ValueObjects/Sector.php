<?php

namespace Src\Domains\Onboarding\ValueObjects;

/**
 * Value Object : Secteur d'activité
 */
class Sector
{
    private const VALID_SECTORS = [
        'pharmacy' => 'Pharmacie',
        'kiosk' => 'Kiosque',
        'supermarket' => 'Supermarché',
        'butchery' => 'Boucherie',
        'hardware' => 'Quincaillerie',
        'other' => 'Autre'
    ];

    private string $value;

    public function __construct(string $sector)
    {
        if (!array_key_exists($sector, self::VALID_SECTORS)) {
            throw new \InvalidArgumentException("Secteur invalide: $sector");
        }
        
        $this->value = $sector;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return self::VALID_SECTORS[$this->value];
    }

    public function equals(Sector $other): bool
    {
        return $this->value === $other->value;
    }

    public static function getAll(): array
    {
        return self::VALID_SECTORS;
    }
}