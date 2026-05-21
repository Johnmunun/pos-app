<?php

namespace Src\Domain\GlobalCommerce\Inventory\Repositories;

use Src\Domain\GlobalCommerce\Inventory\Entities\Product;

interface ProductRepositoryInterface
{
    public function save(Product $product): void;
    public function update(Product $product): void;
    public function findById(string $id): ?Product;
    public function findBySku(string $shopId, string $sku): ?Product;
    public function findByBarcode(string $shopId, string $barcode): ?Product;
    public function existsBySku(string $shopId, string $sku, ?string $excludeId = null): bool;

    /**
     * @param list<string> $shopIds
     */
    public function existsBySkuInShops(array $shopIds, string $sku, ?string $excludeId = null): bool;
    public function search(string $shopId, string $query, array $filters = []): array;

    /**
     * @return array{
     *     items: list<Product>,
     *     total: int,
     *     current_page: int,
     *     last_page: int,
     *     per_page: int,
     *     from: int|null,
     *     to: int|null
     * }
     */
    public function searchPaginated(string $shopId, string $query, array $filters = [], int $page = 1, int $perPage = 25): array;

    public function delete(string $id): void;
}

