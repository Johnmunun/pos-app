<?php

namespace Src\Domain\GlobalCommerce\Inventory\Repositories;

use Src\Domain\GlobalCommerce\Inventory\Entities\Category;

interface CategoryRepositoryInterface
{
    public function save(Category $category): void;
    public function update(Category $category): void;
    public function findById(string $id): ?Category;
    public function findByName(string $shopId, string $name): ?Category;
    public function existsByName(string $shopId, string $name, ?string $excludeId = null): bool;
    public function findTree(string $shopId): array;
    public function delete(string $id): void;
    /**
     * @return Category[]
     */
    public function findByShop(string $shopId, array $filters = []): array;
}

