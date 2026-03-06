<?php

namespace Src\Infrastructure\GlobalCommerce\Inventory\Persistence;

use Illuminate\Database\Eloquent\Builder;
use Src\Domain\GlobalCommerce\Inventory\Entities\StockTransfer;
use Src\Domain\GlobalCommerce\Inventory\Entities\StockTransferItem;
use Src\Domain\GlobalCommerce\Inventory\Repositories\StockTransferRepositoryInterface;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\GcStockTransferModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\GcStockTransferItemModel;

class EloquentStockTransferRepository implements StockTransferRepositoryInterface
{
    public function save(StockTransfer $transfer): void
    {
        GcStockTransferModel::query()->create([
            'id' => $transfer->getId(),
            'tenant_id' => $transfer->getTenantId(),
            'reference' => $transfer->getReference(),
            'from_shop_id' => $transfer->getFromShopId(),
            'to_shop_id' => $transfer->getToShopId(),
            'status' => $transfer->getStatus(),
            'created_by' => $transfer->getCreatedBy(),
            'validated_by' => $transfer->getValidatedBy(),
            'validated_at' => $transfer->getValidatedAt(),
            'notes' => $transfer->getNotes(),
        ]);
    }

    public function update(StockTransfer $transfer): void
    {
        GcStockTransferModel::query()
            ->where('id', $transfer->getId())
            ->update([
                'status' => $transfer->getStatus(),
                'validated_by' => $transfer->getValidatedBy(),
                'validated_at' => $transfer->getValidatedAt(),
                'notes' => $transfer->getNotes(),
            ]);
    }

    public function findById(string $id): ?StockTransfer
    {
        $model = GcStockTransferModel::query()->with('items')->find($id);
        return $model ? $this->mapToEntity($model) : null;
    }

    public function findByIdAndTenant(string $id, string $tenantId): ?StockTransfer
    {
        $model = GcStockTransferModel::query()
            ->with('items')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    /**
     * @param array<string, mixed> $filters
     * @return StockTransfer[]
     */
    public function findByTenant(string $tenantId, array $filters = []): array
    {
        $query = $this->applyFilters(
            GcStockTransferModel::query()->with('items')->where('tenant_id', $tenantId),
            $filters
        );

        return $query->orderByDesc('created_at')
            ->get()
            ->map(fn ($m) => $this->mapToEntity($m))
            ->all();
    }

    /**
     * @param array<string, mixed> $filters
     * @return StockTransfer[]
     */
    public function findAll(array $filters = []): array
    {
        $query = $this->applyFilters(GcStockTransferModel::query()->with('items'), $filters);

        return $query->orderByDesc('created_at')
            ->get()
            ->map(fn ($m) => $this->mapToEntity($m))
            ->all();
    }

    public function delete(string $id): void
    {
        GcStockTransferModel::query()->where('id', $id)->delete();
    }

    /**
     * @param Builder<GcStockTransferModel> $query
     * @return Builder<GcStockTransferModel>
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['from_shop_id'])) {
            $query->where('from_shop_id', $filters['from_shop_id']);
        }
        if (!empty($filters['to_shop_id'])) {
            $query->where('to_shop_id', $filters['to_shop_id']);
        }
        if (!empty($filters['reference'])) {
            $query->where('reference', 'like', '%' . $filters['reference'] . '%');
        }
        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query;
    }

    private function mapToEntity(GcStockTransferModel $model): StockTransfer
    {
        $transfer = new StockTransfer(
            $model->id,
            (string) $model->tenant_id,
            $model->reference,
            (string) $model->from_shop_id,
            (string) $model->to_shop_id,
            $model->status,
            (int) $model->created_by,
            $model->validated_by ? (int) $model->validated_by : null,
            $model->created_at ? \DateTimeImmutable::createFromMutable($model->created_at) : new \DateTimeImmutable(),
            $model->validated_at ? \DateTimeImmutable::createFromMutable($model->validated_at) : null,
            $model->notes
        );

        $items = [];
        foreach ($model->items ?? [] as $itemModel) {
            $items[] = new StockTransferItem(
                $itemModel->id,
                $itemModel->stock_transfer_id,
                $itemModel->product_id,
                (float) $itemModel->quantity,
                $itemModel->created_at ? \DateTimeImmutable::createFromMutable($itemModel->created_at) : new \DateTimeImmutable()
            );
        }
        $transfer->setItems($items);

        return $transfer;
    }
}
