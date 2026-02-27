<?php

namespace Src\Domain\Quincaillerie\Repositories;

use Src\Domain\Quincaillerie\Entities\Product;
use Src\Domain\Quincaillerie\ValueObjects\ProductCode;

interface ProductRepositoryInterface
{
    public function save(Product $product): void;
    public function findById(string $id): ?Product;
    public function findByCode(ProductCode $code, string $shopId): ?Product;
    public function findByShop(string $shopId, array $filters = []): array;
    public function findByCategory(string $categoryId, array $filters = []): array;
    public function search(string $shopId, string $query, array $filters = []): array;
    public function delete(string $id): void;
    public function update(Product $product): void;
    /** @param string|null $shopId Si fourni, vérifie l'unicité dans ce shop uniquement */
    public function existsByCode(string $code, ?string $excludeId = null, ?string $shopId = null): bool;
    public function getLowStockProducts(string $shopId, int $threshold = 10): array;
}
