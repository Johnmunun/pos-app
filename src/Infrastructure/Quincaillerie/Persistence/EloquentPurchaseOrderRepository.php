<?php

declare(strict_types=1);

namespace Src\Infrastructure\Quincaillerie\Persistence;

use DateTimeImmutable;
use Src\Domain\Quincaillerie\Entities\PurchaseOrder;
use Src\Domain\Quincaillerie\Repositories\PurchaseOrderRepositoryInterface;
use Src\Infrastructure\Quincaillerie\Models\PurchaseOrderModel;
use Src\Shared\ValueObjects\Money;

class EloquentPurchaseOrderRepository implements PurchaseOrderRepositoryInterface
{
    public function save(PurchaseOrder $purchaseOrder): void
    {
        $depotId = request()->session()->get('current_depot_id');
        PurchaseOrderModel::updateOrCreate(
            ['id' => $purchaseOrder->getId()],
            [
                'shop_id' => $purchaseOrder->getShopId(),
                'depot_id' => $depotId ? (int) $depotId : null,
                'supplier_id' => $purchaseOrder->getSupplierId(),
                'status' => $purchaseOrder->getStatus(),
                'total_amount' => $purchaseOrder->getTotal()->getAmount(),
                'currency' => $purchaseOrder->getCurrency(),
                'ordered_at' => $purchaseOrder->getOrderedAt(),
                'expected_at' => $purchaseOrder->getExpectedAt(),
                'received_at' => $purchaseOrder->getReceivedAt(),
                'created_by' => $purchaseOrder->getCreatedBy(),
            ]
        );
    }

    public function findById(string $id): ?PurchaseOrder
    {
        $model = PurchaseOrderModel::find($id);

        if (!$model) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    /**
     * @return PurchaseOrder[]
     */
    public function findByShop(string $shopId, ?string $status = null, ?DateTimeImmutable $from = null, ?DateTimeImmutable $to = null): array
    {
        $query = PurchaseOrderModel::query()->where('shop_id', $shopId);

        if ($status) {
            $query->where('status', $status);
        }

        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (PurchaseOrderModel $model) => $this->mapToEntity($model))
            ->toArray();
    }

    private function mapToEntity(PurchaseOrderModel $model): PurchaseOrder
    {
        $currency = $model->currency ?? 'USD';

        return new PurchaseOrder(
            $model->id,
            $model->shop_id,
            $model->supplier_id,
            $model->status,
            new Money((float) $model->total_amount, $currency),
            $currency,
            $model->ordered_at ? new DateTimeImmutable($model->ordered_at->format('Y-m-d H:i:s')) : null,
            $model->expected_at ? new DateTimeImmutable($model->expected_at->format('Y-m-d H:i:s')) : null,
            $model->received_at ? new DateTimeImmutable($model->received_at->format('Y-m-d H:i:s')) : null,
            (int) $model->created_by,
            new DateTimeImmutable($model->created_at->format('Y-m-d H:i:s'))
        );
    }
}
