<?php

declare(strict_types=1);

namespace Src\Domain\Quincaillerie\Repositories;

use DateTimeImmutable;
use Src\Domain\Quincaillerie\Entities\PurchaseOrder;

interface PurchaseOrderRepositoryInterface
{
    public function save(PurchaseOrder $purchaseOrder): void;

    public function findById(string $id): ?PurchaseOrder;

    /**
     * @return PurchaseOrder[]
     */
    public function findByShop(string $shopId, ?string $status = null, ?DateTimeImmutable $from = null, ?DateTimeImmutable $to = null): array;
}
