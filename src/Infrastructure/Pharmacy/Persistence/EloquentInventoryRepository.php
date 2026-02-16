<?php

declare(strict_types=1);

namespace Src\Infrastructure\Pharmacy\Persistence;

use DateTimeImmutable;
use Src\Domain\Pharmacy\Entities\Inventory;
use Src\Domain\Pharmacy\Repositories\InventoryRepositoryInterface;
use Src\Infrastructure\Pharmacy\Models\InventoryModel;

/**
 * Implémentation Eloquent du repository Inventory
 */
class EloquentInventoryRepository implements InventoryRepositoryInterface
{
    public function save(Inventory $inventory): void
    {
        InventoryModel::updateOrCreate(
            ['id' => $inventory->getId()],
            [
                'shop_id' => $inventory->getShopId(),
                'reference' => $inventory->getReference(),
                'status' => $inventory->getStatus(),
                'started_at' => $inventory->getStartedAt()?->format('Y-m-d H:i:s'),
                'validated_at' => $inventory->getValidatedAt()?->format('Y-m-d H:i:s'),
                'created_by' => $inventory->getCreatedBy(),
                'validated_by' => $inventory->getValidatedBy(),
            ]
        );
    }

    public function findById(string $id): ?Inventory
    {
        $model = InventoryModel::with('items')->find($id);
        
        if (!$model) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function findByIdAndShop(string $id, string $shopId): ?Inventory
    {
        $model = InventoryModel::with('items')
            ->where('id', $id)
            ->where('shop_id', $shopId)
            ->first();
        
        if (!$model) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    /**
     * @inheritDoc
     */
    public function findByShop(string $shopId, array $filters = []): array
    {
        $query = InventoryModel::query()
            ->with(['items', 'creator', 'validator'])
            ->where('shop_id', $shopId)
            ->orderBy('created_at', 'desc');

        $this->applyFilters($query, $filters);

        /** @var \Illuminate\Database\Eloquent\Collection<int, InventoryModel> $results */
        $results = $query->get();
        
        return $results->map(fn(InventoryModel $model) => $this->toDomainEntity($model))->toArray();
    }

    /**
     * @inheritDoc
     */
    public function findAll(array $filters = []): array
    {
        $query = InventoryModel::query()
            ->with(['items', 'creator', 'validator'])
            ->orderBy('created_at', 'desc');

        $this->applyFilters($query, $filters);

        /** @var \Illuminate\Database\Eloquent\Collection<int, InventoryModel> $results */
        $results = $query->get();
        
        return $results->map(fn(InventoryModel $model) => $this->toDomainEntity($model))->toArray();
    }

    public function delete(Inventory $inventory): void
    {
        InventoryModel::query()->where('id', $inventory->getId())->delete();
    }

    /**
     * Applique les filtres à la requête
     * 
     * @param \Illuminate\Database\Eloquent\Builder<InventoryModel> $query
     * @param array<string, mixed> $filters
     */
    private function applyFilters(\Illuminate\Database\Eloquent\Builder $query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        if (!empty($filters['reference'])) {
            $query->where('reference', 'like', '%' . $filters['reference'] . '%');
        }
    }

    /**
     * Convertit un model Eloquent en entité Domain
     */
    private function toDomainEntity(InventoryModel $model): Inventory
    {
        return new Inventory(
            $model->id,
            $model->shop_id,
            $model->reference,
            $model->status,
            $model->started_at ? new DateTimeImmutable($model->started_at->format('Y-m-d H:i:s')) : null,
            $model->validated_at ? new DateTimeImmutable($model->validated_at->format('Y-m-d H:i:s')) : null,
            (int) $model->created_by,
            $model->validated_by ? (int) $model->validated_by : null,
            new DateTimeImmutable($model->created_at->format('Y-m-d H:i:s')),
            $model->updated_at ? new DateTimeImmutable($model->updated_at->format('Y-m-d H:i:s')) : null
        );
    }
}
