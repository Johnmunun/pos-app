<?php

namespace Src\Domain\Quincaillerie\Repositories;

use Src\Domain\Quincaillerie\Entities\Category;

interface CategoryRepositoryInterface
{
    public function save(Category $category): void;
    public function findById(string $id): ?Category;
    public function findByName(string $name, string $shopId): ?Category;
    public function findByShop(string $shopId, bool $activeOnly = true): array;
    public function findByParent(string $parentId): array;
    public function getTree(string $shopId): array;
    public function delete(string $id): void;
    public function update(Category $category): void;
    public function existsByName(string $name, string $shopId, ?string $excludeId = null): bool;
}
