<?php

namespace Src\Domain\Pharmacy\Repositories;

use DateTimeImmutable;
use Src\Domain\Pharmacy\Entities\PurchaseOrder;

interface PurchaseOrderRepositoryInterface
{
    public function save(PurchaseOrder $purchaseOrder): void;

    public function findById(string $id): ?PurchaseOrder;

    /**
     * @return PurchaseOrder[]
     */
    public function findByShop(string $shopId, ?string $status = null, ?DateTimeImmutable $from = null, ?DateTimeImmutable $to = null): array;
}

