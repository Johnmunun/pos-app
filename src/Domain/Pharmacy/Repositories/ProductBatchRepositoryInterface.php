<?php

declare(strict_types=1);

namespace Src\Domain\Pharmacy\Repositories;

use DateTimeImmutable;
use Src\Domain\Pharmacy\Entities\ProductBatch;

/**
 * Repository interface for ProductBatch entity.
 */
interface ProductBatchRepositoryInterface
{
    /**
     * Save a new batch or update an existing one.
     */
    public function save(ProductBatch $batch): void;

    /**
     * Update an existing batch.
     */
    public function update(ProductBatch $batch): void;

    /**
     * Find a batch by its ID.
     */
    public function findById(string $id): ?ProductBatch;

    /**
     * Find all active batches for a product.
     */
    public function findByProduct(string $productId): array;

    /**
     * Find all active batches for a product with available stock, ordered by expiration date (FIFO).
     * 
     * @return ProductBatch[]
     */
    public function findAvailableByProductFifo(string $productId): array;

    /**
     * Find all batches in a shop.
     * 
     * @return ProductBatch[]
     */
    public function findByShop(string $shopId): array;

    /**
     * Find batches by purchase order.
     * 
     * @return ProductBatch[]
     */
    public function findByPurchaseOrder(string $purchaseOrderId): array;

    /**
     * Find expired batches in a shop.
     * 
     * @return ProductBatch[]
     */
    public function findExpiredByShop(string $shopId, ?DateTimeImmutable $asOf = null): array;

    /**
     * Find batches expiring within the given days.
     * 
     * @return ProductBatch[]
     */
    public function findExpiringByShop(string $shopId, int $days = 30, ?DateTimeImmutable $asOf = null): array;

    /**
     * Get total stock for a product across all batches.
     */
    public function getTotalStockByProduct(string $productId): int;

    /**
     * Find batch by product and batch number.
     */
    public function findByProductAndBatchNumber(string $productId, string $batchNumber): ?ProductBatch;

    /**
     * Get the nearest expiration date for a product.
     */
    public function getNearestExpirationByProduct(string $productId): ?DateTimeImmutable;

    /**
     * Count expired batches in a shop.
     */
    public function countExpiredByShop(string $shopId): int;

    /**
     * Count expiring batches in a shop.
     */
    public function countExpiringByShop(string $shopId, int $days = 30): int;

    /**
     * Delete a batch (soft delete preferred).
     */
    public function delete(string $id): void;

    /**
     * Search batches with filters.
     * 
     * @param array{
     *     shop_id: string,
     *     product_id?: string,
     *     status?: string,
     *     search?: string,
     *     from_date?: DateTimeImmutable,
     *     to_date?: DateTimeImmutable
     * } $filters
     * @return ProductBatch[]
     */
    public function search(array $filters, int $limit = 50, int $offset = 0): array;

    /**
     * Count batches matching filters.
     * 
     * @param array{
     *     shop_id: string,
     *     product_id?: string,
     *     status?: string,
     *     search?: string,
     *     from_date?: DateTimeImmutable,
     *     to_date?: DateTimeImmutable
     * } $filters
     */
    public function countByFilters(array $filters): int;
}
