<?php

namespace Src\Infrastructure\Pharmacy\Persistence;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Src\Domain\Pharmacy\Entities\StockMovement;
use Src\Domain\Pharmacy\Repositories\StockMovementRepositoryInterface;
use Src\Infrastructure\Pharmacy\Models\StockMovementModel;
use Src\Infrastructure\Pharmacy\Models\ProductModel;
use Src\Shared\ValueObjects\Quantity;

class EloquentStockMovementRepository implements StockMovementRepositoryInterface
{
    public function save(StockMovement $movement): void
    {
        $depotId = request()->session()->get('current_depot_id');
        StockMovementModel::query()->create([
            'id' => $movement->getId(),
            'shop_id' => $movement->getShopId(),
            'depot_id' => $depotId ? (int) $depotId : null,
            'product_id' => $movement->getProductId(),
            'type' => $movement->getType(),
            'quantity' => $movement->getQuantity()->getValue(),
            'reference' => $movement->getReference(),
            'created_by' => $movement->getCreatedBy(),
            'created_at' => $movement->getCreatedAt(),
        ]);
    }

    public function findByProduct(string $productId, string $shopId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, StockMovementModel> $models */
        $models = StockMovementModel::query()
            ->where('product_id', $productId)
            ->where('shop_id', $shopId)
            ->orderBy('created_at', 'desc')
            ->get();

        $result = [];
        foreach ($models as $model) {
            $result[] = $this->mapToEntity($model);
        }
        return $result;
    }

    /**
     * @param string $shopId
     * @param array<string, mixed> $filters
     * @return StockMovement[]
     */
    public function findByShopWithFilters(string $shopId, array $filters = []): array
    {
        $query = $this->buildFilteredQuery($shopId, $filters);
        
        /** @var \Illuminate\Database\Eloquent\Collection<int, StockMovementModel> $models */
        $models = $query->orderBy('created_at', 'desc')
            ->limit(1000) // Limite de sécurité
            ->get();

        $result = [];
        foreach ($models as $model) {
            $result[] = $this->mapToEntity($model);
        }
        return $result;
    }

    public function findById(string $id, string $shopId): ?StockMovement
    {
        /** @var StockMovementModel|null $model */
        $model = StockMovementModel::query()
            ->where('id', $id)
            ->where('shop_id', $shopId)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    /**
     * @param string $shopId
     * @param array<string, mixed> $filters
     * @return int
     */
    public function countByShop(string $shopId, array $filters = []): int
    {
        return $this->buildFilteredQuery($shopId, $filters)->count();
    }

    /**
     * Construit une requête filtrée
     *
     * @param string $shopId
     * @param array<string, mixed> $filters
     * @return Builder<StockMovementModel>
     */
    private function buildFilteredQuery(string $shopId, array $filters): Builder
    {
        $query = StockMovementModel::query()->where('shop_id', $shopId);

        // Filtre par product_id
        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        // Filtre par code produit
        if (!empty($filters['product_code'])) {
            $productIds = ProductModel::query()
                ->where('shop_id', $shopId)
                ->where('code', 'like', '%' . $filters['product_code'] . '%')
                ->pluck('id')
                ->toArray();
            $query->whereIn('product_id', $productIds);
        }

        // Filtre par nom produit
        if (!empty($filters['product_name'])) {
            $productIds = ProductModel::query()
                ->where('shop_id', $shopId)
                ->where('name', 'like', '%' . $filters['product_name'] . '%')
                ->pluck('id')
                ->toArray();
            $query->whereIn('product_id', $productIds);
        }

        // Filtre par type
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filtre par date début
        if (!empty($filters['from'])) {
            $fromDate = $filters['from'] instanceof DateTimeImmutable 
                ? $filters['from']->format('Y-m-d 00:00:00')
                : $filters['from'] . ' 00:00:00';
            $query->where('created_at', '>=', $fromDate);
        }

        // Filtre par date fin
        if (!empty($filters['to'])) {
            $toDate = $filters['to'] instanceof DateTimeImmutable
                ? $filters['to']->format('Y-m-d 23:59:59')
                : $filters['to'] . ' 23:59:59';
            $query->where('created_at', '<=', $toDate);
        }

        return $query;
    }

    /**
     * Mappe un modèle Eloquent vers l'entité domain
     */
    private function mapToEntity(StockMovementModel $model): StockMovement
    {
        return new StockMovement(
            $model->id,
            $model->shop_id,
            $model->product_id,
            $model->type,
            new Quantity((float) $model->quantity),
            $model->reference ?? '',
            (int) $model->created_by,
            new DateTimeImmutable($model->created_at ?? 'now')
        );
    }
}

