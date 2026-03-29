<?php

namespace Src\Domains\StoreProvisioning;

/**
 * Mode de démarrage boutique à l'inscription.
 */
final class StoreStartMode
{
    public const EMPTY_STORE = 'empty_store';

    public const PRECONFIGURED_STORE = 'preconfigured_store';

    private const VALID = [
        self::EMPTY_STORE => true,
        self::PRECONFIGURED_STORE => true,
    ];

    public static function assertValid(string $value): void
    {
        if (!isset(self::VALID[$value])) {
            throw new \InvalidArgumentException('Mode de démarrage boutique invalide: '.$value);
        }
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_keys(self::VALID);
    }
}
