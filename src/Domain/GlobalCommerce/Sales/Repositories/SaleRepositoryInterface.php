<?php

namespace Src\Domain\GlobalCommerce\Sales\Repositories;

use Src\Domain\GlobalCommerce\Sales\Entities\Sale;

interface SaleRepositoryInterface
{
    public function save(Sale $sale): void;
    public function findById(string $id): ?Sale;
    /** @return Sale[] */
    public function findByShop(string $shopId, int $limit = 50, int $offset = 0): array;
}
