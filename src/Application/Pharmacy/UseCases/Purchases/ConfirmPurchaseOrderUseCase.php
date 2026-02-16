<?php

namespace Src\Application\Pharmacy\UseCases\Purchases;

use Illuminate\Support\Facades\DB;
use Src\Domain\Pharmacy\Entities\PurchaseOrder;
use Src\Domain\Pharmacy\Repositories\PurchaseOrderRepositoryInterface;

class ConfirmPurchaseOrderUseCase
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

            if ($po->getStatus() !== PurchaseOrder::STATUS_DRAFT) {
                throw new \LogicException('Only draft purchase orders can be confirmed');
            }

            $po->confirm();
            $this->purchaseOrderRepository->save($po);
        });
    }
}

