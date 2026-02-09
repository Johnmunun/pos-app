<?php

namespace Src\Domain\Pharmacy\Repositories;

use Src\Domain\Pharmacy\Entities\Batch;
use Src\Domain\Pharmacy\ValueObjects\ExpiryDate;

interface BatchRepositoryInterface
{
    public function save(Batch $batch): void;
    public function findById(string $id): ?Batch;
    public function findByProduct(string $productId): array;
    public function findByShop(string $shopId, array $filters = []): array;
    public function findByBatchNumber(string $batchNumber, string $shopId): ?Batch;
    public function getExpiringSoon(string $shopId, int $days = 30): array;
    public function getExpired(string $shopId): array;
    public function getLowStock(string $shopId, int $threshold = 10): array;
    public function consume(string $batchId, int $quantity): void;
    public function addStock(string $batchId, int $quantity): void;
    public function delete(string $id): void;
    public function update(Batch $batch): void;
}