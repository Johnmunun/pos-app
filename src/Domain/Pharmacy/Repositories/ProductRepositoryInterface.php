<?php

namespace Src\Domain\Pharmacy\Repositories;

use Src\Domain\Pharmacy\Entities\Product;
use Src\Domain\Pharmacy\ValueObjects\ProductCode;

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
    public function existsByCode(string $code, ?string $excludeId = null): bool;
    public function getLowStockProducts(string $shopId, int $threshold = 10): array;
    public function getExpiredProducts(string $shopId): array;
    public function getExpiringSoon(string $shopId, int $days = 30): array;
    public function findByType(string $shopId, string $type): array;
}