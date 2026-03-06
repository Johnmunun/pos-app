<?php

namespace Src\Domain\GlobalCommerce\Inventory\Repositories;

use Src\Domain\GlobalCommerce\Inventory\Entities\StockTransfer;

interface StockTransferRepositoryInterface
{
    public function save(StockTransfer $transfer): void;

    public function update(StockTransfer $transfer): void;

    public function findById(string $id): ?StockTransfer;

    public function findByIdAndTenant(string $id, string $tenantId): ?StockTransfer;

    /**
     * @param array<string, mixed> $filters
     * @return StockTransfer[]
     */
    public function findByTenant(string $tenantId, array $filters = []): array;

    /**
     * @param array<string, mixed> $filters
     * @return StockTransfer[]
     */
    public function findAll(array $filters = []): array;

    public function delete(string $id): void;
}
