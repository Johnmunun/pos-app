<?php

namespace Src\Domain\Pharmacy\Repositories;

use Src\Domain\Pharmacy\Entities\PurchaseOrderLine;

interface PurchaseOrderLineRepositoryInterface
{
    public function save(PurchaseOrderLine $line): void;

    /**
     * @param string $purchaseOrderId
     * @return PurchaseOrderLine[]
     */
    public function findByPurchaseOrder(string $purchaseOrderId): array;

    public function deleteByPurchaseOrder(string $purchaseOrderId): void;
}

