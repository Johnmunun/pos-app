<?php

declare(strict_types=1);

namespace Src\Infrastructure\Quincaillerie\Persistence;

use Src\Domain\Quincaillerie\Entities\PurchaseOrderLine;
use Src\Domain\Quincaillerie\Repositories\PurchaseOrderLineRepositoryInterface;
use Src\Infrastructure\Quincaillerie\Models\PurchaseOrderLineModel;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;
use DateTimeImmutable;

class EloquentPurchaseOrderLineRepository implements PurchaseOrderLineRepositoryInterface
{
    public function save(PurchaseOrderLine $line): void
    {
        PurchaseOrderLineModel::updateOrCreate(
            ['id' => $line->getId()],
            [
                'purchase_order_id' => $line->getPurchaseOrderId(),
                'product_id' => $line->getProductId(),
                'ordered_quantity' => $line->getOrderedQuantity()->getValue(),
                'received_quantity' => $line->getReceivedQuantity()->getValue(),
                'unit_cost_amount' => $line->getUnitCost()->getAmount(),
                'currency' => $line->getUnitCost()->getCurrency(),
                'line_total_amount' => $line->getLineTotal()->getAmount(),
            ]
        );
    }

    /**
     * @return PurchaseOrderLine[]
     */
    public function findByPurchaseOrder(string $purchaseOrderId): array
    {
        return PurchaseOrderLineModel::query()
            ->where('purchase_order_id', $purchaseOrderId)
            ->get()
            ->map(fn (PurchaseOrderLineModel $model) => $this->mapToEntity($model))
            ->toArray();
    }

    private function mapToEntity(PurchaseOrderLineModel $model): PurchaseOrderLine
    {
        $currency = $model->currency ?? 'USD';

        return new PurchaseOrderLine(
            $model->id,
            $model->purchase_order_id,
            $model->product_id,
            new Quantity((float) $model->ordered_quantity),
            new Quantity((float) $model->received_quantity),
            new Money((float) $model->unit_cost_amount, $currency),
            new Money((float) $model->line_total_amount, $currency),
            new DateTimeImmutable($model->created_at->toDateTimeString())
        );
    }
}
