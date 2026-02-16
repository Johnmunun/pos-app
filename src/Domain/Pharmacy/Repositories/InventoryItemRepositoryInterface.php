<?php

declare(strict_types=1);

namespace Src\Domain\Pharmacy\Repositories;

use Src\Domain\Pharmacy\Entities\InventoryItem;

/**
 * Interface Repository pour les items d'inventaire
 */
interface InventoryItemRepositoryInterface
{
    /**
     * Sauvegarde un item d'inventaire
     */
    public function save(InventoryItem $item): void;

    /**
     * Sauvegarde plusieurs items d'inventaire
     * 
     * @param InventoryItem[] $items
     */
    public function saveMany(array $items): void;

    /**
     * Trouve un item par son ID
     */
    public function findById(string $id): ?InventoryItem;

    /**
     * Trouve un item par inventaire et produit
     */
    public function findByInventoryAndProduct(string $inventoryId, string $productId): ?InventoryItem;

    /**
     * Récupère tous les items d'un inventaire
     * 
     * @return InventoryItem[]
     */
    public function findByInventory(string $inventoryId): array;

    /**
     * Supprime tous les items d'un inventaire
     */
    public function deleteByInventory(string $inventoryId): void;

    /**
     * Met à jour la quantité comptée d'un item
     */
    public function updateCountedQuantity(string $itemId, int $countedQuantity): void;
}
