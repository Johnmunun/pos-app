<?php

declare(strict_types=1);

namespace Src\Infrastructure\Quincaillerie\Persistence;

use DateTimeImmutable;
use Src\Domain\Quincaillerie\Entities\InventoryItem;
use Src\Domain\Quincaillerie\Repositories\InventoryItemRepositoryInterface;
use Src\Infrastructure\Quincaillerie\Models\InventoryItemModel;

/**
 * Implémentation Eloquent du repository InventoryItem - Module Quincaillerie
 */
class EloquentInventoryItemRepository implements InventoryItemRepositoryInterface
{
    public function save(InventoryItem $item): void
    {
        InventoryItemModel::updateOrCreate(
            ['id' => $item->getId()],
            [
                'inventory_id' => $item->getInventoryId(),
                'product_id' => $item->getProductId(),
                'system_quantity' => $item->getSystemQuantity(),
                'counted_quantity' => $item->getCountedQuantity(),
                'difference' => $item->getDifference(),
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function saveMany(array $items): void
    {
        foreach ($items as $item) {
            $this->save($item);
        }
    }

    public function findById(string $id): ?InventoryItem
    {
        /** @var InventoryItemModel|null $model */
        $model = InventoryItemModel::query()->find($id);
        
        if (!$model) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function findByInventoryAndProduct(string $inventoryId, string $productId): ?InventoryItem
    {
        /** @var InventoryItemModel|null $model */
        $model = InventoryItemModel::query()
            ->where('inventory_id', $inventoryId)
            ->where('product_id', $productId)
            ->first();
        
        if (!$model) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    /**
     * @inheritDoc
     */
    public function findByInventory(string $inventoryId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, InventoryItemModel> $results */
        $results = InventoryItemModel::query()
            ->with('product')
            ->where('inventory_id', $inventoryId)
            ->get();

        return $results->map(fn(InventoryItemModel $model) => $this->toDomainEntity($model))->toArray();
    }

    public function deleteByInventory(string $inventoryId): void
    {
        InventoryItemModel::query()->where('inventory_id', $inventoryId)->delete();
    }

    public function updateCountedQuantity(string $itemId, float $countedQuantity): void
    {
        /** @var InventoryItemModel|null $model */
        $model = InventoryItemModel::query()->find($itemId);
        
        if ($model !== null) {
            $systemQuantity = (float) $model->system_quantity;
            $difference = $countedQuantity - $systemQuantity;
            
            $model->update([
                'counted_quantity' => $countedQuantity,
                'difference' => $difference,
            ]);
        }
    }

    /**
     * Convertit un model Eloquent en entité Domain
     */
    private function toDomainEntity(InventoryItemModel $model): InventoryItem
    {
        return new InventoryItem(
            $model->id,
            $model->inventory_id,
            $model->product_id,
            (float) $model->system_quantity,
            $model->counted_quantity !== null ? (float) $model->counted_quantity : null,
            (float) $model->difference,
            new DateTimeImmutable($model->created_at->format('Y-m-d H:i:s')),
            $model->updated_at ? new DateTimeImmutable($model->updated_at->format('Y-m-d H:i:s')) : null
        );
    }
}
