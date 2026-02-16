<?php

namespace Src\Domain\Pharmacy\Repositories;

use DateTimeImmutable;
use Src\Domain\Pharmacy\Entities\Sale;

interface SaleRepositoryInterface
{
    public function save(Sale $sale): void;

    public function findById(string $id): ?Sale;

    /**
     * @return Sale[]
     */
    public function findByShop(string $shopId, ?DateTimeImmutable $from = null, ?DateTimeImmutable $to = null): array;

    /**
     * @return Sale[]
     */
    public function findByCustomer(string $shopId, string $customerId): array;
}

