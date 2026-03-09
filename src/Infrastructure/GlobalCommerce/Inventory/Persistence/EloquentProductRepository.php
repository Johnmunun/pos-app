<?php

namespace Src\Infrastructure\GlobalCommerce\Inventory\Persistence;

use Src\Domain\GlobalCommerce\Inventory\Entities\Product;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\GcStockMovementModel;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function save(Product $product): void
    {
        $beforeStock = null;
        $existing = ProductModel::find($product->getId());
        if ($existing) {
            $beforeStock = (float) ($existing->stock ?? 0);
        }

        $afterStock = (float) $product->getStock()->getValue();

        ProductModel::updateOrCreate(
            ['id' => $product->getId()],
            [
                'shop_id' => $product->getShopId(),
                'sku' => $product->getSku(),
                'barcode' => $product->getBarcode(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'category_id' => $product->getCategoryId(),
                'purchase_price_amount' => $product->getPurchasePrice()->getAmount(),
                'purchase_price_currency' => $product->getPurchasePrice()->getCurrency(),
                'sale_price_amount' => $product->getSalePrice()->getAmount(),
                'sale_price_currency' => $product->getSalePrice()->getCurrency(),
                'stock' => $product->getStock()->getValue(),
                'minimum_stock' => $product->getMinimumStock()->getValue(),
                'is_weighted' => $product->isWeighted(),
                'has_expiration' => $product->hasExpiration(),
                'is_active' => $product->isActive(),
            ]
        );

        // Journaliser automatiquement les variations de stock (mouvements).
        // Cette approche permet d'alimenter l'historique sans dépendre du contexte métier (vente/achat/etc.).
        if (Schema::hasTable('gc_stock_movements')) {
            $delta = $beforeStock === null ? $afterStock : ($afterStock - $beforeStock);
            if (abs($delta) > 0.0000001) {
                $type = $delta > 0 ? 'IN' : 'OUT';
                GcStockMovementModel::create([
                    'id' => Uuid::uuid4()->toString(),
                    'shop_id' => (int) $product->getShopId(),
                    'product_id' => $product->getId(),
                    'type' => $type,
                    'quantity' => abs($delta),
                    'reference' => $beforeStock === null ? 'Stock initial' : 'Mise à jour stock',
                    'reference_type' => 'auto',
                    'reference_id' => null,
                    'created_by' => auth()->id(),
                ]);
            }
        }
    }

    public function update(Product $product): void
    {
        $this->save($product);
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

    public function findBySku(string $shopId, string $sku): ?Product
    {
        $model = ProductModel::where('shop_id', $shopId)->where('sku', $sku)->first();
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByBarcode(string $shopId, string $barcode): ?Product
    {
        $model = ProductModel::where('shop_id', $shopId)->where('barcode', $barcode)->first();
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function existsBySku(string $shopId, string $sku, ?string $excludeId = null): bool
    {
        $query = ProductModel::where('shop_id', $shopId)->where('sku', $sku);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function search(string $shopId, string $query, array $filters = []): array
    {
        $q = ProductModel::where('shop_id', $shopId);
        if ($query !== '') {
            $q->where(function ($qb) use ($query) {
                $qb->where('name', 'like', '%' . $query . '%')
                    ->orWhere('sku', 'like', '%' . $query . '%')
                    ->orWhere('barcode', 'like', '%' . $query . '%')
                    ->orWhere('description', 'like', '%' . $query . '%');
            });
        }
        if (!empty($filters['category_id'])) {
            $q->where('category_id', $filters['category_id']);
        }
        if (isset($filters['is_active']) && $filters['is_active'] === true) {
            $q->where('is_active', true);
        }
        if (isset($filters['is_published_ecommerce']) && $filters['is_published_ecommerce'] === true) {
            $q->where('is_published_ecommerce', true);
        }
        $models = $q->with('category')->orderBy('name')->get();
        return $models->map(fn (ProductModel $m) => $this->toDomainEntity($m))->toArray();
    }

    public function delete(string $id): void
    {
        ProductModel::destroy($id);
    }

    private function toDomainEntity(ProductModel $model): Product
    {
        return new Product(
            $model->id,
            (string) $model->shop_id,
            $model->sku,
            $model->barcode,
            $model->name,
            $model->description ?? '',
            $model->category_id,
            new Money($model->purchase_price_amount, $model->purchase_price_currency),
            new Money($model->sale_price_amount, $model->sale_price_currency),
            new Quantity($model->stock),
            new Quantity($model->minimum_stock),
            (bool) $model->is_weighted,
            (bool) $model->has_expiration,
            (bool) $model->is_active
        );
    }
}
