<?php

namespace Src\Infrastructure\GlobalCommerce\Inventory\Persistence;

use Src\Domain\GlobalCommerce\Inventory\Entities\Category;
use Src\Domain\GlobalCommerce\Inventory\Repositories\CategoryRepositoryInterface;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\CategoryModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function save(Category $category): void
    {
        CategoryModel::updateOrCreate(
            ['id' => $category->getId()],
            [
                'shop_id' => $category->getShopId(),
                'name' => $category->getName(),
                'description' => $category->getDescription(),
                'parent_id' => $category->getParentId(),
                'sort_order' => $category->getSortOrder(),
                'is_active' => $category->isActive(),
            ]
        );
    }

    public function update(Category $category): void
    {
        $this->save($category);
    }

    public function findById(string $id): ?Category
    {
        try {
            $model = CategoryModel::findOrFail($id);
            return $this->toDomainEntity($model);
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    public function findByName(string $shopId, string $name): ?Category
    {
        $model = CategoryModel::where('shop_id', $shopId)
            ->where('name', $name)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function existsByName(string $shopId, string $name, ?string $excludeId = null): bool
    {
        $query = CategoryModel::where('shop_id', $shopId)->where('name', $name);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function findTree(string $shopId): array
    {
        return $this->findTreeForShopIds([(string) $shopId]);
    }

    public function findTreeForShopIds(array $shopIds): array
    {
        if ($shopIds === []) {
            return [];
        }
        $roots = CategoryModel::whereIn('shop_id', $shopIds)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->with('children')->orderBy('sort_order')->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $roots->map(fn (CategoryModel $m) => $this->buildTree($m))->toArray();
    }

    /**
     * @return Category[]
     */
    public function findByShop(string $shopId, array $filters = []): array
    {
        $query = CategoryModel::where('shop_id', $shopId);

        if (isset($filters['active'])) {
            $query->where('is_active', (bool) $filters['active']);
        }

        return $query->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (CategoryModel $model) => $this->toDomainEntity($model))
            ->toArray();
    }

    public function delete(string $id): void
    {
        CategoryModel::destroy($id);
    }

    private function toDomainEntity(CategoryModel $model): Category
    {
        $category = new Category(
            $model->id,
            (string) $model->shop_id,
            $model->name,
            $model->description ?? '',
            $model->parent_id,
            $model->sort_order ?? 0,
            (bool) $model->is_active
        );
        return $category;
    }

    private function buildTree(CategoryModel $model): array
    {
        $category = $this->toDomainEntity($model);
        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'description' => $category->getDescription(),
            'sort_order' => $category->getSortOrder(),
            'parent_id' => $category->getParentId(),
            'is_active' => $category->isActive(),
            'children' => $model->children->map(fn (CategoryModel $c) => $this->buildTree($c))->toArray(),
        ];
    }
}
