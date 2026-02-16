<?php

namespace Src\Domain\Pharmacy\Repositories;

use Src\Domain\Pharmacy\Entities\StockTransferItem;

interface StockTransferItemRepositoryInterface
{
    /**
     * Sauvegarde un item
     */
    public function save(StockTransferItem $item): void;

    /**
     * Sauvegarde plusieurs items
     * 
     * @param StockTransferItem[] $items
     */
    public function saveMany(array $items): void;

    /**
     * Met à jour un item
     */
    public function update(StockTransferItem $item): void;

    /**
     * Trouve un item par son ID
     */
    public function findById(string $id): ?StockTransferItem;

    /**
     * Liste les items d'un transfert
     * 
     * @return StockTransferItem[]
     */
    public function findByTransfer(string $transferId): array;

    /**
     * Trouve un item par transfert et produit
     */
    public function findByTransferAndProduct(string $transferId, string $productId): ?StockTransferItem;

    /**
     * Supprime les items d'un transfert
     */
    public function deleteByTransfer(string $transferId): void;

    /**
     * Supprime un item
     */
    public function delete(string $id): void;
}
