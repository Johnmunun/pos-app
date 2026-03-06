<?php

namespace Src\Infrastructure\GlobalCommerce\Inventory\Persistence;

use Src\Domain\GlobalCommerce\Inventory\Entities\StockTransferItem;
use Src\Domain\GlobalCommerce\Inventory\Repositories\StockTransferItemRepositoryInterface;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\GcStockTransferItemModel;

class EloquentStockTransferItemRepository implements StockTransferItemRepositoryInterface
{
    public function save(StockTransferItem $item): void
    {
        GcStockTransferItemModel::query()->create([
            'id' => $item->getId(),
            'stock_transfer_id' => $item->getStockTransferId(),
            'product_id' => $item->getProductId(),
            'quantity' => $item->getQuantity(),
        ]);
    }

    public function saveMany(array $items): void
    {
        foreach ($items as $item) {
            $this->save($item);
        }
    }

    public function update(StockTransferItem $item): void
    {
        GcStockTransferItemModel::query()
            ->where('id', $item->getId())
            ->update(['quantity' => $item->getQuantity()]);
    }

    public function findById(string $id): ?StockTransferItem
    {
        $model = GcStockTransferItemModel::query()->find($id);
        return $model ? $this->mapToEntity($model) : null;
    }

    /**
     * @return StockTransferItem[]
     */
    public function findByTransfer(string $transferId): array
    {
        return GcStockTransferItemModel::query()
            ->where('stock_transfer_id', $transferId)
            ->get()
            ->map(fn ($m) => $this->mapToEntity($m))
            ->all();
    }

    public function findByTransferAndProduct(string $transferId, string $productId): ?StockTransferItem
    {
        $model = GcStockTransferItemModel::query()
            ->where('stock_transfer_id', $transferId)
            ->where('product_id', $productId)
            ->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    public function delete(string $id): void
    {
        GcStockTransferItemModel::query()->where('id', $id)->delete();
    }

    public function deleteByTransfer(string $transferId): void
    {
        GcStockTransferItemModel::query()->where('stock_transfer_id', $transferId)->delete();
    }

    private function mapToEntity(GcStockTransferItemModel $model): StockTransferItem
    {
        return new StockTransferItem(
            $model->id,
            $model->stock_transfer_id,
            $model->product_id,
            (float) $model->quantity,
            $model->created_at ? \DateTimeImmutable::createFromMutable($model->created_at) : new \DateTimeImmutable()
        );
    }
}
