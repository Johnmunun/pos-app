<?php

namespace Src\Domain\GlobalCommerce\Inventory\Repositories;

use Src\Domain\GlobalCommerce\Inventory\Entities\StockTransferItem;

interface StockTransferItemRepositoryInterface
{
    public function save(StockTransferItem $item): void;

    /**
     * @param StockTransferItem[] $items
     */
    public function saveMany(array $items): void;

    public function update(StockTransferItem $item): void;

    public function findById(string $id): ?StockTransferItem;

    /**
     * @return StockTransferItem[]
     */
    public function findByTransfer(string $transferId): array;

    public function findByTransferAndProduct(string $transferId, string $productId): ?StockTransferItem;

    public function delete(string $id): void;

    public function deleteByTransfer(string $transferId): void;
}
