<?php

namespace Src\Infrastructure\GlobalCommerce\Procurement\Persistence;

use Src\Domain\GlobalCommerce\Procurement\Entities\Purchase;
use Src\Domain\GlobalCommerce\Procurement\Repositories\PurchaseRepositoryInterface;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\PurchaseModel;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\PurchaseLineModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Ramsey\Uuid\Uuid;

class EloquentPurchaseRepository implements PurchaseRepositoryInterface
{
    public function save(Purchase $purchase): void
    {
        PurchaseModel::updateOrCreate(
            ['id' => $purchase->getId()],
            [
                'shop_id' => $purchase->getShopId(),
                'supplier_id' => $purchase->getSupplierId(),
                'status' => $purchase->getStatus(),
                'total_amount' => $purchase->getTotalAmount(),
                'currency' => $purchase->getCurrency(),
                'expected_at' => $purchase->getExpectedAt(),
                'received_at' => $purchase->getReceivedAt(),
                'notes' => $purchase->getNotes(),
            ]
        );

        PurchaseLineModel::where('purchase_id', $purchase->getId())->delete();
        foreach ($purchase->getLines() as $line) {
            PurchaseLineModel::create([
                'id' => Uuid::uuid4()->toString(),
                'purchase_id' => $purchase->getId(),
                'product_id' => $line['product_id'],
                'ordered_quantity' => $line['ordered_quantity'],
                'received_quantity' => $line['received_quantity'] ?? 0,
                'unit_cost' => $line['unit_cost'],
                'line_total' => $line['line_total'],
                'product_name' => $line['product_name'],
            ]);
        }
    }

    public function findById(string $id): ?Purchase
    {
        try {
            $model = PurchaseModel::with('lines')->findOrFail($id);
            return $this->toDomainEntity($model);
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    /** @return Purchase[] */
    public function findByShop(string $shopId, int $limit = 50, int $offset = 0): array
    {
        $models = PurchaseModel::with(['lines', 'supplier'])
            ->where('shop_id', $shopId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $models->map(fn ($m) => $this->toDomainEntity($m))->toArray();
    }

    private function toDomainEntity(PurchaseModel $model): Purchase
    {
        $lines = $model->lines->map(fn ($l) => [
            'product_id' => $l->product_id,
            'product_name' => $l->product_name,
            'ordered_quantity' => (float) $l->ordered_quantity,
            'received_quantity' => (float) $l->received_quantity,
            'unit_cost' => (float) $l->unit_cost,
            'line_total' => (float) $l->line_total,
        ])->toArray();

        return new Purchase(
            $model->id,
            (string) $model->shop_id,
            $model->supplier_id,
            $model->status,
            (float) $model->total_amount,
            $model->currency,
            $model->expected_at,
            $model->received_at,
            $model->notes,
            $lines,
            $model->created_at
        );
    }
}
