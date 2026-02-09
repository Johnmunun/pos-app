<?php

namespace Src\Infrastructure\Pharmacy\Persistence;

use Src\Domain\Pharmacy\Entities\Batch;
use Src\Domain\Pharmacy\Repositories\BatchRepositoryInterface;
use Src\Domain\Pharmacy\ValueObjects\ExpiryDate;
use Src\Shared\ValueObjects\Quantity;
use Src\Infrastructure\Pharmacy\Models\BatchModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use DateTimeImmutable;

class EloquentBatchRepository implements BatchRepositoryInterface
{
    public function save(Batch $batch): void
    {
        BatchModel::updateOrCreate(
            ['id' => $batch->getId()],
            [
                'shop_id' => $batch->getShopId(),
                'product_id' => $batch->getProductId(),
                'batch_number' => $batch->getBatchNumber(),
                'expiry_date' => $batch->getExpiryDate()->getDate(),
                'quantity' => $batch->getQuantity()->getValue(),
                'initial_quantity' => $batch->getInitialQuantity()->getValue(),
                'supplier_id' => $batch->getSupplierId(),
                'purchase_order_id' => $batch->getPurchaseOrderId(),
                'created_at' => $batch->getCreatedAt(),
                'updated_at' => $batch->getUpdatedAt(),
            ]
        );
    }

    public function findById(string $id): ?Batch
    {
        try {
            $model = BatchModel::findOrFail($id);
            return $this->toDomainEntity($model);
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    public function findByProduct(string $productId): array
    {
        $models = BatchModel::where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date')
            ->get();
            
        return $models->map(fn($model) => $this->toDomainEntity($model))->toArray();
    }

    public function findByShop(string $shopId, array $filters = []): array
    {
        $query = BatchModel::where('shop_id', $shopId);
        
        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }
        
        if (isset($filters['in_stock']) && $filters['in_stock']) {
            $query->where('quantity', '>', 0);
        }
        
        $models = $query->with(['product', 'supplier'])
            ->orderBy('expiry_date')
            ->get();
            
        return $models->map(fn($model) => $this->toDomainEntity($model))->toArray();
    }

    public function findByBatchNumber(string $batchNumber, string $shopId): ?Batch
    {
        $model = BatchModel::where('batch_number', $batchNumber)
            ->where('shop_id', $shopId)
            ->first();
            
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function getExpiringSoon(string $shopId, int $days = 30): array
    {
        $expiryDate = (new DateTimeImmutable())->add(new \DateInterval("P{$days}D"));
        
        $models = BatchModel::where('shop_id', $shopId)
            ->where('expiry_date', '<=', $expiryDate)
            ->where('expiry_date', '>=', new DateTimeImmutable())
            ->where('quantity', '>', 0)
            ->with(['product'])
            ->orderBy('expiry_date')
            ->get();
            
        return $models->map(fn($model) => $this->toDomainEntity($model))->toArray();
    }

    public function getExpired(string $shopId): array
    {
        $models = BatchModel::where('shop_id', $shopId)
            ->where('expiry_date', '<', new DateTimeImmutable())
            ->where('quantity', '>', 0)
            ->with(['product'])
            ->orderBy('expiry_date')
            ->get();
            
        return $models->map(fn($model) => $this->toDomainEntity($model))->toArray();
    }

    public function getLowStock(string $shopId, int $threshold = 10): array
    {
        $models = BatchModel::where('shop_id', $shopId)
            ->where('quantity', '<=', $threshold)
            ->where('quantity', '>', 0)
            ->with(['product'])
            ->orderBy('quantity')
            ->get();
            
        return $models->map(fn($model) => $this->toDomainEntity($model))->toArray();
    }

    public function consume(string $batchId, int $quantity): void
    {
        $batch = $this->findById($batchId);
        if ($batch) {
            $batch->consume(new Quantity($quantity));
            $this->update($batch);
        }
    }

    public function addStock(string $batchId, int $quantity): void
    {
        $batch = $this->findById($batchId);
        if ($batch) {
            $batch->addStock(new Quantity($quantity));
            $this->update($batch);
        }
    }

    public function delete(string $id): void
    {
        BatchModel::destroy($id);
    }

    public function update(Batch $batch): void
    {
        $this->save($batch);
    }

    private function toDomainEntity(BatchModel $model): Batch
    {
        return new Batch(
            $model->id,
            $model->shop_id,
            $model->product_id,
            $model->batch_number,
            new ExpiryDate(new DateTimeImmutable($model->expiry_date->format('Y-m-d'))),
            new Quantity($model->quantity),
            $model->supplier_id,
            $model->purchase_order_id
        );
    }
}