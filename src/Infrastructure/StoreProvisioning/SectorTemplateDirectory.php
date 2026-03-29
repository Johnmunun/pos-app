<?php

namespace Src\Infrastructure\StoreProvisioning;

/**
 * Résout le dossier de fichiers Excel (database/data-templates/...) selon le secteur métier.
 */
final class SectorTemplateDirectory
{
    public static function folderForSector(string $sector): string
    {
        return match ($sector) {
            'pharmacy' => 'pharmacy',
            'hardware' => 'hardware',
            'global_commerce' => 'global-commerce',
            'ecommerce' => 'ecommerce',
            'kiosk', 'supermarket', 'butchery', 'other' => 'global-commerce',
            default => throw new \InvalidArgumentException('Secteur sans pack métier pré-configuré: '.$sector),
        };
    }

    public static function basePath(string $sector): string
    {
        return database_path('data-templates/'.self::folderForSector($sector));
    }
}
