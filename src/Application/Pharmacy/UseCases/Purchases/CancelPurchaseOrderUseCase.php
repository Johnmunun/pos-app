<?php

namespace Src\Application\Pharmacy\UseCases\Purchases;

use Illuminate\Support\Facades\DB;
use Src\Domain\Pharmacy\Entities\PurchaseOrder;
use Src\Domain\Pharmacy\Repositories\PurchaseOrderRepositoryInterface;

class CancelPurchaseOrderUseCase
{
    public function __construct(
        private PurchaseOrderRepositoryInterface $purchaseOrderRepository
    ) {}

    public function execute(string $purchaseOrderId): void
    {
        DB::transaction(function () use ($purchaseOrderId): void {
            $po = $this->purchaseOrderRepository->findById($purchaseOrderId);

            if (!$po) {
                throw new \InvalidArgumentException('Purchase order not found');
            }

            if (in_array($po->getStatus(), [PurchaseOrder::STATUS_RECEIVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED], true)) {
                throw new \LogicException('Cannot cancel a purchase order that has received items');
            }

            $po->cancel();
            $this->purchaseOrderRepository->save($po);
        });
    }
}

