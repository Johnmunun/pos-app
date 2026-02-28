<?php

declare(strict_types=1);

namespace Src\Domain\Quincaillerie\Repositories;

use Src\Domain\Quincaillerie\Entities\PurchaseOrderLine;

interface PurchaseOrderLineRepositoryInterface
{
    public function save(PurchaseOrderLine $line): void;

    /**
     * @return PurchaseOrderLine[]
     */
    public function findByPurchaseOrder(string $purchaseOrderId): array;
}
