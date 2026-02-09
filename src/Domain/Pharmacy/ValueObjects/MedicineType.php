<?php

namespace Src\Domain\Pharmacy\ValueObjects;

use InvalidArgumentException;

class MedicineType
{
    private const VALID_TYPES = [
        'MEDICINE',      // Médicament
        'PARAPHARMACY',  // Parapharmacie
        'DEVICE',        // Dispositif médical
        'VACCINE',       // Vaccin
        'NUTRITION'      // Nutrition
    ];

    private string $value;

    public function __construct(string $type)
    {
        $type = strtoupper($type);
        
        if (!in_array($type, self::VALID_TYPES)) {
            throw new InvalidArgumentException(
                'Invalid medicine type. Valid types: ' . implode(', ', self::VALID_TYPES)
            );
        }

        $this->value = $type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isMedicine(): bool
    {
        return $this->value === 'MEDICINE';
    }

    public function isParapharmacy(): bool
    {
        return $this->value === 'PARAPHARMACY';
    }

    public function isDevice(): bool
    {
        return $this->value === 'DEVICE';
    }

    public function isVaccine(): bool
    {
        return $this->value === 'VACCINE';
    }

    public function isNutrition(): bool
    {
        return $this->value === 'NUTRITION';
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function getAllTypes(): array
    {
        return self::VALID_TYPES;
    }
}