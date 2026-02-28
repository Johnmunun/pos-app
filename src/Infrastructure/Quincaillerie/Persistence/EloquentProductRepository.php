<?php

namespace Src\Infrastructure\Quincaillerie\Persistence;

use Src\Domain\Quincaillerie\Entities\Product;
use Src\Domain\Quincaillerie\Repositories\ProductRepositoryInterface;
use Src\Domain\Quincaillerie\ValueObjects\ProductCode;
use Src\Domain\Quincaillerie\ValueObjects\TypeUnite;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;
use Src\Infrastructure\Quincaillerie\Models\ProductModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Repository Eloquent Produit - Module Quincaillerie.
 * Aucune dépendance Pharmacy.
 */
class EloquentProductRepository implements ProductRepositoryInterface
{
    public function save(Product $product): void
    {
        // Le depot_id sera géré par le contrôleur via DepotFilterService
        // On préserve le depot_id existant si le produit existe déjà
        $depotId = null;
        $existingModel = ProductModel::find($product->getId());
        if ($existingModel) {
            $depotId = $existingModel->depot_id;
        } else {
            // Pour les nouveaux produits, utiliser le dépôt de la session si disponible
            $depotId = request()->session()->get('current_depot_id');
        }
        
        // Récupérer les données supplémentaires depuis le modèle si elles existent (pour préserver)
        $model = ProductModel::find($product->getId());
        $additionalData = [];
        
        if ($model) {
            // Préserver les données supplémentaires existantes si elles ne sont pas écrasées
            $additionalData = [
                'image_path' => $model->image_path,
                'image_type' => $model->image_type,
                'price_normal' => $model->price_normal,
                'price_reduced' => $model->price_reduced,
                'price_reduction_percent' => $model->price_reduction_percent,
                'price_non_negotiable' => $model->price_non_negotiable,
                'price_wholesale_normal' => $model->price_wholesale_normal,
                'price_wholesale_reduced' => $model->price_wholesale_reduced,
                'price_non_negotiable_wholesale' => $model->price_non_negotiable_wholesale,
            ];
        }
        
        ProductModel::updateOrCreate(
            ['id' => $product->getId()],
            array_merge([
                'shop_id' => $product->getShopId(),
                'depot_id' => $depotId ? (int) $depotId : null,
                'code' => $product->getCode()->getValue(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price_amount' => $product->getPrice()->getAmount(),
                'price_currency' => $product->getPrice()->getCurrency(),
                'stock' => $product->getStock()->getValue(),
                'type_unite' => $product->getTypeUnite()->getValue(),
                'quantite_par_unite' => $product->getQuantiteParUnite(),
                'est_divisible' => $product->estDivisible(),
                'minimum_stock' => $product->getMinimumStock()->getValue(),
                'category_id' => $product->getCategoryId(),
                'is_active' => $product->isActive(),
            ], $additionalData)
        );
    }
    
    /**
     * Sauvegarder les données supplémentaires (image et prix multiples)
     */
    public function saveAdditionalData(string $productId, array $data): void
    {
        $model = ProductModel::find($productId);
        if (!$model) {
            return;
        }
        
        // Pour image_path et image_type, permettre null (pour suppression d'image)
        // Pour les autres champs, filtrer les valeurs null
        $updateData = array_filter($data, function($value, $key) {
            // Toujours inclure image_path et image_type même si null (pour permettre la suppression)
            if ($key === 'image_path' || $key === 'image_type') {
                return true;
            }
            return $value !== null;
        }, ARRAY_FILTER_USE_BOTH);
        
        \Illuminate\Support\Facades\Log::info('EloquentProductRepository::saveAdditionalData', [
            'product_id' => $productId,
            'update_data' => $updateData
        ]);
        
        $model->update($updateData);
        
        // Vérifier que l'image a bien été sauvegardée
        $model->refresh();
        \Illuminate\Support\Facades\Log::info('Product model after update', [
            'product_id' => $productId,
            'image_path' => $model->image_path,
            'image_type' => $model->image_type
        ]);
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
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('code', 'like', '%' . $filters['search'] . '%');
            });
        }

        $models = $query->with(['category'])->orderBy('name')->get();

        return $models->map(fn ($model) => $this->toDomainEntity($model))->toArray();
    }

    public function findByCategory(string $categoryId, array $filters = []): array
    {
        $query = ProductModel::where('category_id', $categoryId);
        if (isset($filters['active']) && $filters['active']) {
            $query->where('is_active', true);
        }
        $models = $query->with(['category'])->orderBy('name')->get();

        return $models->map(fn ($model) => $this->toDomainEntity($model))->toArray();
    }

    public function search(string $shopId, string $query, array $filters = []): array
    {
        $models = ProductModel::where('shop_id', $shopId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                    ->orWhere('code', 'like', '%' . $query . '%')
                    ->orWhere('description', 'like', '%' . $query . '%');
            })
            ->with(['category'])
            ->orderBy('name')
            ->get();

        return $models->map(fn ($model) => $this->toDomainEntity($model))->toArray();
    }

    public function delete(string $id): void
    {
        ProductModel::destroy($id);
    }

    public function update(Product $product): void
    {
        $this->save($product);
    }

    public function existsByCode(string $code, ?string $excludeId = null, ?string $shopId = null): bool
    {
        $query = ProductModel::where('code', $code);
        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        }
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function getLowStockProducts(string $shopId, int $threshold = 10): array
    {
        $models = ProductModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->where(function ($q) use ($threshold) {
                $q->whereColumn('stock', '<=', 'minimum_stock')
                    ->orWhere('stock', '<=', $threshold);
            })
            ->with(['category'])
            ->orderBy('stock')
            ->get();

        return $models->map(fn ($model) => $this->toDomainEntity($model))->toArray();
    }

    private function toDomainEntity(ProductModel $model): Product
    {
        $code = new ProductCode($model->code ?? '');
        $price = new Money((float) $model->price_amount, $model->price_currency ?? 'USD');
        $stock = new Quantity((float) ($model->stock ?? 0));
        $typeUnite = isset($model->type_unite) ? new TypeUnite($model->type_unite) : new TypeUnite(TypeUnite::UNITE);
        $quantiteParUnite = (int) ($model->quantite_par_unite ?? 1);
        $estDivisible = (bool) ($model->est_divisible ?? true);
        $minimumStock = new Quantity((float) ($model->minimum_stock ?? 0));

        return new Product(
            $model->id,
            (string) $model->shop_id,
            $code,
            $model->name,
            $model->description ?? '',
            $price,
            $stock,
            $typeUnite,
            $quantiteParUnite,
            $estDivisible,
            (string) $model->category_id,
            $minimumStock
        );
    }
}
