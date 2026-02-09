<?php

namespace Src\Infrastructure\Pharmacy\Persistence;

use Src\Domain\Pharmacy\Entities\Product;
use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\ValueObjects\ProductCode;
use Src\Domain\Pharmacy\ValueObjects\ExpiryDate;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;
use Src\Infrastructure\Pharmacy\Models\ProductModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use DateTimeImmutable;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function save(Product $product): void
    {
        ProductModel::updateOrCreate(
            ['id' => $product->getId()],
            [
                'shop_id' => $product->getShopId(),
                'code' => $product->getCode()->getValue(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'category_id' => $product->getCategoryId(),
                'price_amount' => $product->getPrice()->getAmount(),
                'price_currency' => $product->getPrice()->getCurrency(),
                'cost_amount' => $product->getCost()?->getAmount(),
                'cost_currency' => $product->getCost()?->getCurrency(),
                'minimum_stock' => $product->getMinimumStock()->getValue(),
                'current_stock' => $product->getStock()->getValue(),
                'unit' => $product->getUnit(),
                'medicine_type' => $product->getMedicineType()?->getValue(),
                'dosage' => $product->getDosage()?->getValue(),
                'prescription_required' => $product->requiresPrescription(),
                'manufacturer' => $product->getManufacturer(),
                'supplier_id' => $product->getSupplierId(),
                'is_active' => $product->isActive(),
                'created_at' => $product->getCreatedAt(),
                'updated_at' => $product->getUpdatedAt(),
            ]
        );
    }

    public function findById(string $id): ?Product
    {
        try {
            $model = ProductModel::findOrFail($id);
            return $this->toDomainEntity($model);
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    public function findByCode(ProductCode $code, string $shopId): ?Product
    {
        $model = ProductModel::where('code', $code->getValue())
            ->where('shop_id', $shopId)
            ->first();
            
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByShop(string $shopId, array $filters = []): array
    {
        $query = ProductModel::where('shop_id', $shopId);
        
        if (isset($filters['active']) && $filters['active']) {
            $query->where('is_active', true);
        }
        
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        
        if (isset($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('code', 'like', '%' . $filters['search'] . '%');
            });
        }
        
        $models = $query->with(['category'])
            ->orderBy('name')
            ->get();
            
        return $models->map(fn($model) => $this->toDomainEntity($model))->toArray();
    }

    public function findByCategory(string $categoryId, array $filters = []): array
    {
        $query = ProductModel::where('category_id', $categoryId);
        
        if (isset($filters['active']) && $filters['active']) {
            $query->where('is_active', true);
        }
        
        $models = $query->with(['category'])
            ->orderBy('name')
            ->get();
            
        return $models->map(fn($model) => $this->toDomainEntity($model))->toArray();
    }

    public function search(string $shopId, string $query, array $filters = []): array
    {
        $models = ProductModel::where('shop_id', $shopId)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                  ->orWhere('code', 'like', '%' . $query . '%')
                  ->orWhere('description', 'like', '%' . $query . '%');
            })
            ->with(['category'])
            ->orderBy('name')
            ->get();
            
        return $models->map(fn($model) => $this->toDomainEntity($model))->toArray();
    }

    public function delete(string $id): void
    {
        ProductModel::destroy($id);
    }

    public function update(Product $product): void
    {
        $this->save($product);
    }

    public function existsByCode(string $code, ?string $excludeId = null): bool
    {
        $query = ProductModel::where('code', $code);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    public function getLowStockProducts(string $shopId, int $threshold = 10): array
    {
        $models = ProductModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->where('current_stock', '<=', $threshold)
            ->with(['category'])
            ->orderBy('current_stock')
            ->get();
            
        return $models->map(fn($model) => $this->toDomainEntity($model))->toArray();
    }

    public function getExpiredProducts(string $shopId): array
    {
        // This would join with batches to find expired products
        // For now, return empty array
        return [];
    }

    public function getExpiringSoon(string $shopId, int $days = 30): array
    {
        // This would join with batches to find expiring products
        // For now, return empty array
        return [];
    }

    public function findByType(string $shopId, string $type): array
    {
        $models = ProductModel::where('shop_id', $shopId)
            ->where('medicine_type', $type)
            ->where('is_active', true)
            ->with(['category'])
            ->orderBy('name')
            ->get();
            
        return $models->map(fn($model) => $this->toDomainEntity($model))->toArray();
    }

    private function toDomainEntity(ProductModel $model): Product
    {
        return new Product(
            $model->id,
            $model->shop_id,
            $model->name,
            new ProductCode($model->code),
            $model->description,
            $model->category_id,
            new Money($model->price_amount, $model->price_currency),
            $model->cost_amount ? new Money($model->cost_amount, $model->cost_currency) : null,
            new Quantity($model->minimum_stock),
            new Quantity($model->current_stock),
            $model->unit,
            $model->medicine_type ? new \Src\Domain\Pharmacy\ValueObjects\MedicineType($model->medicine_type) : null,
            $model->dosage ? new \Src\Domain\Pharmacy\ValueObjects\Dosage($model->dosage) : null,
            $model->prescription_required,
            $model->manufacturer,
            $model->supplier_id
        );
    }
}