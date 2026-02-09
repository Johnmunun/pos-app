<?php

namespace Src\Domain\Settings\Repositories;

use Src\Domain\Settings\Entities\StoreSettings;

/**
 * Repository Interface: StoreSettingsRepositoryInterface
 * 
 * Définit le contrat pour la persistance des paramètres de boutique
 */
interface StoreSettingsRepositoryInterface
{
    /**
     * Trouver les paramètres par shop_id
     */
    public function findByShopId(string $shopId): ?StoreSettings;

    /**
     * Sauvegarder (créer ou mettre à jour) les paramètres
     */
    public function save(StoreSettings $settings): void;

    /**
     * Vérifier si des paramètres existent pour un shop
     */
    public function existsForShop(string $shopId): bool;

    /**
     * Supprimer les paramètres d'un shop
     */
    public function deleteByShopId(string $shopId): void;
}
