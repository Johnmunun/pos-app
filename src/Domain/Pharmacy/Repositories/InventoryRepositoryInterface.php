<?php

declare(strict_types=1);

namespace Src\Domain\Pharmacy\Repositories;

use Src\Domain\Pharmacy\Entities\Inventory;

/**
 * Interface Repository pour les inventaires
 */
interface InventoryRepositoryInterface
{
    /**
     * Sauvegarde un inventaire
     */
    public function save(Inventory $inventory): void;

    /**
     * Trouve un inventaire par son ID
     */
    public function findById(string $id): ?Inventory;

    /**
     * Trouve un inventaire par son ID et shop_id (sécurité multi-tenant)
     */
    public function findByIdAndShop(string $id, string $shopId): ?Inventory;

    /**
     * Récupère tous les inventaires d'une boutique (optionnellement filtrés par dépôt)
     *
     * @param string $shopId
     * @param array<string, mixed> $filters (status, from, to, reference, depot_id)
     * @return Inventory[]
     */
    public function findByShop(string $shopId, array $filters = []): array;

    /**
     * Récupère tous les inventaires (ROOT only)
     * 
     * @param array<string, mixed> $filters
     * @return Inventory[]
     */
    public function findAll(array $filters = []): array;

    /**
     * Supprime un inventaire
     */
    public function delete(Inventory $inventory): void;
}
