<?php

namespace Src\Domain\GlobalCommerce\Procurement\Repositories;

use Src\Domain\GlobalCommerce\Procurement\Entities\Purchase;

interface PurchaseRepositoryInterface
{
    public function save(Purchase $purchase): void;
    public function findById(string $id): ?Purchase;
    /** @return Purchase[] */
    public function findByShop(string $shopId, int $limit = 50, int $offset = 0): array;
}
