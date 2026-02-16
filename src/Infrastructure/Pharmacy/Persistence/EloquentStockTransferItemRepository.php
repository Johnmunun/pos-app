<?php

namespace Src\Infrastructure\Pharmacy\Persistence;

use DateTimeImmutable;
use Src\Domain\Pharmacy\Entities\StockTransferItem;
use Src\Domain\Pharmacy\Repositories\StockTransferItemRepositoryInterface;
use Src\Infrastructure\Pharmacy\Models\StockTransferItemModel;

class EloquentStockTransferItemRepository implements StockTransferItemRepositoryInterface
{
    public function save(StockTransferItem $item): void
    {
        StockTransferItemModel::query()->create([
            'id' => $item->getId(),
            'stock_transfer_id' => $item->getStockTransferId(),
            'product_id' => $item->getProductId(),
            'quantity' => $item->getQuantity(),
            'created_at' => $item->getCreatedAt(),
        ]);
    }

    /**
     * @param StockTransferItem[] $items
     */
    public function saveMany(array $items): void
    {
        foreach ($items as $item) {
            $this->save($item);
        }
    }

    public function update(StockTransferItem $item): void
    {
        StockTransferItemModel::query()
            ->where('id', $item->getId())
            ->update([
                'quantity' => $item->getQuantity(),
            ]);
    }

    public function findById(string $id): ?StockTransferItem
    {
        /** @var StockTransferItemModel|null $model */
        $model = StockTransferItemModel::query()->find($id);

        if ($model === null) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    /**
     * @return StockTransferItem[]
     */
    public function findByTransfer(string $transferId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, StockTransferItemModel> $models */
        $models = StockTransferItemModel::query()
            ->where('stock_transfer_id', $transferId)
            ->get();

        $result = [];
        foreach ($models as $model) {
            $result[] = $this->mapToEntity($model);
        }
        return $result;
    }

    public function findByTransferAndProduct(string $transferId, string $productId): ?StockTransferItem
    {
        /** @var StockTransferItemModel|null $model */
        $model = StockTransferItemModel::query()
            ->where('stock_transfer_id', $transferId)
            ->where('product_id', $productId)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    public function deleteByTransfer(string $transferId): void
    {
        StockTransferItemModel::query()
            ->where('stock_transfer_id', $transferId)
            ->delete();
    }

    public function delete(string $id): void
    {
        StockTransferItemModel::query()->where('id', $id)->delete();
    }

    /**
     * Mappe un modèle Eloquent vers l'entité domain
     */
    private function mapToEntity(StockTransferItemModel $model): StockTransferItem
    {
        return new StockTransferItem(
            $model->id,
            $model->stock_transfer_id,
            $model->product_id,
            $model->quantity,
            new DateTimeImmutable($model->created_at ?? 'now')
        );
    }
}
