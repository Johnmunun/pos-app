<?php

declare(strict_types=1);

namespace Src\Infrastructure\Pharmacy\Persistence;

use DateTimeImmutable;
use Src\Domain\Pharmacy\Entities\ProductBatch;
use Src\Domain\Pharmacy\Repositories\ProductBatchRepositoryInterface;
use Src\Domain\Pharmacy\ValueObjects\BatchNumber;
use Src\Domain\Pharmacy\ValueObjects\ExpirationDate;
use Src\Infrastructure\Pharmacy\Models\ProductBatchModel;
use Src\Shared\ValueObjects\Quantity;

/**
 * Eloquent implementation of ProductBatchRepositoryInterface.
 */
final class EloquentProductBatchRepository implements ProductBatchRepositoryInterface
{
    public function save(ProductBatch $batch): void
    {
        ProductBatchModel::create([
            'id' => $batch->getId(),
            'shop_id' => $batch->getShopId(),
            'product_id' => $batch->getProductId(),
            'batch_number' => $batch->getBatchNumber()->getValue(),
            'quantity' => $batch->getQuantity()->getValue(),
            'expiration_date' => $batch->getExpirationDate()->format('Y-m-d'),
            'purchase_order_id' => $batch->getPurchaseOrderId(),
            'purchase_order_line_id' => $batch->getPurchaseOrderLineId(),
            'is_active' => $batch->isActive(),
        ]);
    }

    public function update(ProductBatch $batch): void
    {
        ProductBatchModel::where('id', $batch->getId())->update([
            'batch_number' => $batch->getBatchNumber()->getValue(),
            'quantity' => $batch->getQuantity()->getValue(),
            'expiration_date' => $batch->getExpirationDate()->format('Y-m-d'),
            'is_active' => $batch->isActive(),
            'updated_at' => now(),
        ]);
    }

    public function findById(string $id): ?ProductBatch
    {
        $model = ProductBatchModel::find($id);
        
        if (!$model) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function findByProduct(string $productId): array
    {
        /** @var \Illuminate\Support\Collection<int, ProductBatchModel> $models */
        $models = ProductBatchModel::query()
            ->byProduct($productId)
            ->active()
            ->orderBy('expiration_date')
            ->get();

        return $models->map(fn (ProductBatchModel $m) => $this->toDomainEntity($m))->toArray();
    }

    public function findAvailableByProductFifo(string $productId): array
    {
        /** @var \Illuminate\Support\Collection<int, ProductBatchModel> $models */
        $models = ProductBatchModel::query()
            ->byProduct($productId)
            ->active()
            ->withStock()
            ->orderBy('expiration_date', 'asc') // FIFO: earliest expiration first
            ->get();

        return $models->map(fn (ProductBatchModel $m) => $this->toDomainEntity($m))->toArray();
    }

    public function findByShop(string $shopId): array
    {
        /** @var \Illuminate\Support\Collection<int, ProductBatchModel> $models */
        $models = ProductBatchModel::query()
            ->byShop($shopId)
            ->active()
            ->withStock()
            ->orderBy('expiration_date')
            ->get();

        return $models->map(fn (ProductBatchModel $m) => $this->toDomainEntity($m))->toArray();
    }

    public function findByPurchaseOrder(string $purchaseOrderId): array
    {
        /** @var \Illuminate\Support\Collection<int, ProductBatchModel> $models */
        $models = ProductBatchModel::query()
            ->where('purchase_order_id', $purchaseOrderId)
            ->orderBy('expiration_date')
            ->get();

        return $models->map(fn (ProductBatchModel $m) => $this->toDomainEntity($m))->toArray();
    }

    public function findExpiredByShop(string $shopId, ?DateTimeImmutable $asOf = null): array
    {
        /** @var \Illuminate\Support\Collection<int, ProductBatchModel> $models */
        $models = ProductBatchModel::query()
            ->byShop($shopId)
            ->active()
            ->withStock()
            ->expired($asOf)
            ->with('product')
            ->orderBy('expiration_date')
            ->get();

        return $models->map(fn (ProductBatchModel $m) => $this->toDomainEntity($m))->toArray();
    }

    public function findExpiringByShop(string $shopId, int $days = 30, ?DateTimeImmutable $asOf = null): array
    {
        /** @var \Illuminate\Support\Collection<int, ProductBatchModel> $models */
        $models = ProductBatchModel::query()
            ->byShop($shopId)
            ->active()
            ->withStock()
            ->expiring($days, $asOf)
            ->with('product')
            ->orderBy('expiration_date')
            ->get();

        return $models->map(fn (ProductBatchModel $m) => $this->toDomainEntity($m))->toArray();
    }

    public function getTotalStockByProduct(string $productId): int
    {
        return (int) ProductBatchModel::query()
            ->byProduct($productId)
            ->active()
            ->sum('quantity');
    }

    public function findByProductAndBatchNumber(string $productId, string $batchNumber): ?ProductBatch
    {
        /** @var ProductBatchModel|null $model */
        $model = ProductBatchModel::query()
            ->byProduct($productId)
            ->where('batch_number', $batchNumber)
            ->active()
            ->first();

        if (!$model) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function getNearestExpirationByProduct(string $productId): ?DateTimeImmutable
    {
        /** @var ProductBatchModel|null $model */
        $model = ProductBatchModel::query()
            ->byProduct($productId)
            ->active()
            ->withStock()
            ->orderBy('expiration_date', 'asc')
            ->first();

        if (!$model) {
            return null;
        }

        return new DateTimeImmutable($model->expiration_date->toDateString());
    }

    public function countExpiredByShop(string $shopId): int
    {
        return ProductBatchModel::query()
            ->byShop($shopId)
            ->active()
            ->withStock()
            ->expired()
            ->count();
    }

    public function countExpiringByShop(string $shopId, int $days = 30): int
    {
        return ProductBatchModel::query()
            ->byShop($shopId)
            ->active()
            ->withStock()
            ->expiring($days)
            ->count();
    }

    public function delete(string $id): void
    {
        ProductBatchModel::where('id', $id)->update(['is_active' => false]);
    }

    public function search(array $filters, int $limit = 50, int $offset = 0): array
    {
        $query = ProductBatchModel::query()
            ->byShop($filters['shop_id'])
            ->active()
            ->with('product');

        // Apply filters
        if (!empty($filters['product_id'])) {
            $query->byProduct($filters['product_id']);
        }

        if (!empty($filters['status'])) {
            $now = new DateTimeImmutable();
            switch ($filters['status']) {
                case 'expired':
                    $query->expired($now);
                    break;
                case 'expiring_soon':
                    $query->expiring(30, $now);
                    break;
                case 'ok':
                    $threshold = $now->modify('+30 days');
                    $query->where('expiration_date', '>', $threshold->format('Y-m-d'));
                    break;
            }
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['from_date'])) {
            /** @var DateTimeImmutable $fromDate */
            $fromDate = $filters['from_date'];
            $query->where('expiration_date', '>=', $fromDate->format('Y-m-d'));
        }

        if (!empty($filters['to_date'])) {
            /** @var DateTimeImmutable $toDate */
            $toDate = $filters['to_date'];
            $query->where('expiration_date', '<=', $toDate->format('Y-m-d'));
        }

        /** @var \Illuminate\Support\Collection<int, ProductBatchModel> $models */
        $models = $query
            ->withStock()
            ->orderBy('expiration_date', 'asc')
            ->skip($offset)
            ->take($limit)
            ->get();

        return $models->map(fn (ProductBatchModel $m) => $this->toDomainEntity($m))->toArray();
    }

    public function countByFilters(array $filters): int
    {
        $query = ProductBatchModel::query()
            ->byShop($filters['shop_id'])
            ->active()
            ->withStock();

        if (!empty($filters['product_id'])) {
            $query->byProduct($filters['product_id']);
        }

        if (!empty($filters['status'])) {
            $now = new DateTimeImmutable();
            switch ($filters['status']) {
                case 'expired':
                    $query->expired($now);
                    break;
                case 'expiring_soon':
                    $query->expiring(30, $now);
                    break;
                case 'ok':
                    $threshold = $now->modify('+30 days');
                    $query->where('expiration_date', '>', $threshold->format('Y-m-d'));
                    break;
            }
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['from_date'])) {
            /** @var DateTimeImmutable $fromDate */
            $fromDate = $filters['from_date'];
            $query->where('expiration_date', '>=', $fromDate->format('Y-m-d'));
        }

        if (!empty($filters['to_date'])) {
            /** @var DateTimeImmutable $toDate */
            $toDate = $filters['to_date'];
            $query->where('expiration_date', '<=', $toDate->format('Y-m-d'));
        }

        return $query->count();
    }

    /**
     * Convert Eloquent model to domain entity.
     */
    private function toDomainEntity(ProductBatchModel $model): ProductBatch
    {
        // Handle expiration date (Carbon from Eloquent cast)
        $expirationDateStr = $model->expiration_date->toDateString();

        // Handle created_at (Carbon from Eloquent)
        $createdAt = $model->created_at !== null
            ? new DateTimeImmutable($model->created_at->toDateTimeString())
            : new DateTimeImmutable();

        // Handle updated_at (Carbon from Eloquent, can be null)
        $updatedAt = $model->updated_at !== null
            ? new DateTimeImmutable($model->updated_at->toDateTimeString())
            : null;

        return ProductBatch::reconstitute(
            id: $model->id,
            shopId: $model->shop_id,
            productId: $model->product_id,
            batchNumber: new BatchNumber($model->batch_number),
            quantity: new Quantity($model->quantity),
            expirationDate: ExpirationDate::fromString($expirationDateStr),
            purchaseOrderId: $model->purchase_order_id,
            purchaseOrderLineId: $model->purchase_order_line_id,
            isActive: $model->is_active,
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );
    }
}
