<?php

namespace Src\Infrastructure\Pharmacy\Persistence;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Src\Domain\Pharmacy\Entities\StockTransfer;
use Src\Domain\Pharmacy\Entities\StockTransferItem;
use Src\Domain\Pharmacy\Repositories\StockTransferRepositoryInterface;
use Src\Infrastructure\Pharmacy\Models\StockTransferModel;
use Src\Infrastructure\Pharmacy\Models\StockTransferItemModel;

class EloquentStockTransferRepository implements StockTransferRepositoryInterface
{
    public function save(StockTransfer $transfer): void
    {
        $depotId = request()->session()->get('current_depot_id');
        StockTransferModel::query()->create([
            'id' => $transfer->getId(),
            'pharmacy_id' => $transfer->getPharmacyId(),
            'reference' => $transfer->getReference(),
            'from_shop_id' => $transfer->getFromShopId(),
            'from_depot_id' => $depotId ? (int) $depotId : null,
            'to_shop_id' => $transfer->getToShopId(),
            'to_depot_id' => null,
            'status' => $transfer->getStatus(),
            'created_by' => $transfer->getCreatedBy(),
            'validated_by' => $transfer->getValidatedBy(),
            'validated_at' => $transfer->getValidatedAt(),
            'notes' => $transfer->getNotes(),
            'created_at' => $transfer->getCreatedAt(),
        ]);
    }

    public function update(StockTransfer $transfer): void
    {
        StockTransferModel::query()
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
        /** @var StockTransferModel|null $model */
        $model = StockTransferModel::query()
            ->with('items')
            ->find($id);

        if ($model === null) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    public function findByIdAndPharmacy(string $id, string $pharmacyId): ?StockTransfer
    {
        /** @var StockTransferModel|null $model */
        $model = StockTransferModel::query()
            ->with('items')
            ->where('id', $id)
            ->where('pharmacy_id', $pharmacyId)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    public function findByReference(string $reference, string $pharmacyId): ?StockTransfer
    {
        /** @var StockTransferModel|null $model */
        $model = StockTransferModel::query()
            ->with('items')
            ->where('reference', $reference)
            ->where('pharmacy_id', $pharmacyId)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    /**
     * @param string $pharmacyId
     * @param array<string, mixed> $filters
     * @return StockTransfer[]
     */
    public function findByPharmacy(string $pharmacyId, array $filters = []): array
    {
        $query = StockTransferModel::query()
            ->with('items')
            ->where('pharmacy_id', $pharmacyId);

        $query = $this->applyFilters($query, $filters);

        /** @var \Illuminate\Database\Eloquent\Collection<int, StockTransferModel> $models */
        $models = $query->orderBy('created_at', 'desc')->get();

        $result = [];
        foreach ($models as $model) {
            $result[] = $this->mapToEntity($model);
        }
        return $result;
    }

    /**
     * @param array<string, mixed> $filters
     * @return StockTransfer[]
     */
    public function findAll(array $filters = []): array
    {
        $query = StockTransferModel::query()->with('items');

        $query = $this->applyFilters($query, $filters);

        /** @var \Illuminate\Database\Eloquent\Collection<int, StockTransferModel> $models */
        $models = $query->orderBy('created_at', 'desc')->get();

        $result = [];
        foreach ($models as $model) {
            $result[] = $this->mapToEntity($model);
        }
        return $result;
    }

    public function delete(string $id): void
    {
        StockTransferModel::query()->where('id', $id)->delete();
    }

    /**
     * Applique les filtres à la requête
     *
     * @param Builder<StockTransferModel> $query
     * @param array<string, mixed> $filters
     * @return Builder<StockTransferModel>
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

        if (!empty($filters['from'])) {
            $fromDate = $filters['from'] instanceof DateTimeImmutable
                ? $filters['from']->format('Y-m-d 00:00:00')
                : $filters['from'] . ' 00:00:00';
            $query->where('created_at', '>=', $fromDate);
        }

        if (!empty($filters['to'])) {
            $toDate = $filters['to'] instanceof DateTimeImmutable
                ? $filters['to']->format('Y-m-d 23:59:59')
                : $filters['to'] . ' 23:59:59';
            $query->where('created_at', '<=', $toDate);
        }

        if (!empty($filters['reference'])) {
            $query->where('reference', 'like', '%' . $filters['reference'] . '%');
        }

        return $query;
    }

    /**
     * Mappe un modèle Eloquent vers l'entité domain
     */
    private function mapToEntity(StockTransferModel $model): StockTransfer
    {
        $transfer = new StockTransfer(
            $model->id,
            $model->pharmacy_id,
            $model->reference,
            (string) $model->from_shop_id,
            (string) $model->to_shop_id,
            $model->status,
            $model->created_by,
            $model->validated_by,
            new DateTimeImmutable($model->created_at ?? 'now'),
            $model->validated_at !== null ? new DateTimeImmutable($model->validated_at) : null,
            $model->notes
        );

        // Mapper les items
        $items = [];
        foreach ($model->items as $itemModel) {
            $items[] = new StockTransferItem(
                $itemModel->id,
                $itemModel->stock_transfer_id,
                $itemModel->product_id,
                $itemModel->quantity,
                new DateTimeImmutable($itemModel->created_at ?? 'now')
            );
        }
        $transfer->setItems($items);

        return $transfer;
    }
}
