<?php

namespace Src\Domains\Onboarding\ValueObjects;

/**
 * Value Object : Type de commerce
 */
class BusinessType
{
    private const VALID_TYPES = [
        'individual' => 'Commerçant individuel',
        'sarl' => 'SARL',
        'sa' => 'SA',
        'sas' => 'SAS',
        'sasu' => 'SASU',
        'association' => 'Association',
        'other' => 'Autre'
    ];

    private string $value;

    public function __construct(string $type)
    {
        if (!array_key_exists($type, self::VALID_TYPES)) {
            throw new \InvalidArgumentException("Type de commerce invalide: $type");
        }
        
        $this->value = $type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return self::VALID_TYPES[$this->value];
    }

    public function equals(BusinessType $other): bool
    {
        return $this->value === $other->value;
    }

    public static function getAll(): array
    {
        return self::VALID_TYPES;
    }
}