<?php

namespace Src\Infrastructure\Quincaillerie\Persistence;

use Src\Domain\Quincaillerie\Entities\Category;
use Src\Domain\Quincaillerie\Repositories\CategoryRepositoryInterface;
use Src\Infrastructure\Quincaillerie\Models\CategoryModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Repository Eloquent Catégorie - Module Quincaillerie.
 * Aucune dépendance Pharmacy.
 */
class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function save(Category $category): void
    {
        $depotId = request()->session()->get('current_depot_id');
        CategoryModel::updateOrCreate(
            ['id' => $category->getId()],
            [
                'shop_id' => $category->getShopId(),
                'depot_id' => $depotId ? (int) $depotId : null,
                'name' => $category->getName(),
                'description' => $category->getDescription(),
                'parent_id' => $category->getParentId(),
                'sort_order' => $category->getSortOrder(),
                'is_active' => $category->isActive(),
            ]
        );
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

    public function findByName(string $name, string $shopId): ?Category
    {
        $model = CategoryModel::where('name', $name)
            ->where('shop_id', $shopId)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByShop(string $shopId, bool $activeOnly = true): array
    {
        $query = CategoryModel::where('shop_id', $shopId);
        if ($activeOnly) {
            $query->where('is_active', true);
        }
        $models = $query->with(['parent', 'children'])->orderBy('sort_order')->orderBy('name')->get();

        return $models->map(fn ($model) => $this->toDomainEntity($model))->toArray();
    }

    public function findByParent(string $parentId): array
    {
        $models = CategoryModel::where('parent_id', $parentId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $models->map(fn ($model) => $this->toDomainEntity($model))->toArray();
    }

    public function getTree(string $shopId): array
    {
        $rootCategories = CategoryModel::where('shop_id', $shopId)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => function ($query) {
                $query->where('is_active', true)->with('children');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $rootCategories->map(fn ($model) => $this->buildTree($model))->toArray();
    }

    public function delete(string $id): void
    {
        CategoryModel::destroy($id);
    }

    public function update(Category $category): void
    {
        $this->save($category);
    }

    public function existsByName(string $name, string $shopId, ?string $excludeId = null): bool
    {
        $query = CategoryModel::where('name', $name)->where('shop_id', $shopId);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    private function toDomainEntity(CategoryModel $model): Category
    {
        $category = new Category(
            $model->id,
            (string) $model->shop_id,
            $model->name,
            $model->description ?? '',
            $model->parent_id,
            $model->sort_order ?? 0
        );
        if (!$model->is_active) {
            $category->deactivate();
        }
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
            'has_parent' => $category->hasParent(),
            'children' => $model->children->map(fn ($child) => $this->buildTree($child))->toArray(),
        ];
    }
}
