<?php

namespace Src\Infrastructure\Pharmacy\Persistence;

use Src\Domain\Pharmacy\Entities\PurchaseOrderLine;
use Src\Domain\Pharmacy\Repositories\PurchaseOrderLineRepositoryInterface;
use Src\Infrastructure\Pharmacy\Models\PurchaseOrderLineModel;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;

class EloquentPurchaseOrderLineRepository implements PurchaseOrderLineRepositoryInterface
{
    public function save(PurchaseOrderLine $line): void
    {
        $currency = $line->getUnitCost()->getCurrency();

        PurchaseOrderLineModel::updateOrCreate(
            ['id' => $line->getId()],
            [
                'purchase_order_id' => $line->getPurchaseOrderId(),
                'product_id' => $line->getProductId(),
                'ordered_quantity' => $line->getOrderedQuantity()->getValue(),
                'received_quantity' => $line->getReceivedQuantity()->getValue(),
                'unit_cost_amount' => $line->getUnitCost()->getAmount(),
                'currency' => $currency,
                'line_total_amount' => $line->getLineTotal()->getAmount(),
            ]
        );
    }

    public function findByPurchaseOrder(string $purchaseOrderId): array
    {
        return PurchaseOrderLineModel::where('purchase_order_id', $purchaseOrderId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function (PurchaseOrderLineModel $model) {
                $currency = $model->currency ?? 'USD';

                return new PurchaseOrderLine(
                    $model->id,
                    $model->purchase_order_id,
                    $model->product_id,
                    new Quantity((int) $model->ordered_quantity),
                    new Quantity((int) $model->received_quantity),
                    new Money((float) $model->unit_cost_amount, $currency),
                    new Money((float) $model->line_total_amount, $currency),
                    new \DateTimeImmutable($model->created_at)
                );
            })
            ->toArray();
    }

    public function deleteByPurchaseOrder(string $purchaseOrderId): void
    {
        PurchaseOrderLineModel::where('purchase_order_id', $purchaseOrderId)->delete();
    }
}

