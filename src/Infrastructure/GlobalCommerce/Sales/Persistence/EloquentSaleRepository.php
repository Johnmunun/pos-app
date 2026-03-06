<?php

namespace Src\Infrastructure\GlobalCommerce\Sales\Persistence;

use Src\Domain\GlobalCommerce\Sales\Entities\Sale;
use Src\Domain\GlobalCommerce\Sales\Repositories\SaleRepositoryInterface;
use Src\Infrastructure\GlobalCommerce\Sales\Models\SaleModel;
use Src\Infrastructure\GlobalCommerce\Sales\Models\SaleLineModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Ramsey\Uuid\Uuid;

class EloquentSaleRepository implements SaleRepositoryInterface
{
    public function save(Sale $sale): void
    {
        $data = [
            'shop_id' => $sale->getShopId(),
            'status' => strtoupper((string) ($sale->getStatus() ?: 'completed')),
            'total_amount' => $sale->getTotalAmount(),
            'currency' => $sale->getCurrency(),
            'customer_name' => $sale->getCustomerName(),
            'notes' => $sale->getNotes(),
        ];
        if ($sale->getCreatedByUserId() !== null) {
            $data['created_by'] = $sale->getCreatedByUserId();
        }
        SaleModel::updateOrCreate(['id' => $sale->getId()], $data);

        SaleLineModel::where('sale_id', $sale->getId())->delete();
        foreach ($sale->getLines() as $line) {
            SaleLineModel::create([
                'id' => Uuid::uuid4()->toString(),
                'sale_id' => $sale->getId(),
                'product_id' => $line['product_id'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'subtotal' => $line['subtotal'],
                'product_name' => $line['product_name'],
            ]);
        }
    }

    public function findById(string $id): ?Sale
    {
        try {
            $model = SaleModel::with('lines')->findOrFail($id);
            return $this->toDomainEntity($model);
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    /** @return Sale[] */
    public function findByShop(string $shopId, int $limit = 50, int $offset = 0): array
    {
        $models = SaleModel::with('lines')
            ->where('shop_id', $shopId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $models->map(fn ($m) => $this->toDomainEntity($m))->toArray();
    }

    private function toDomainEntity(SaleModel $model): Sale
    {
        $lines = $model->lines->map(fn ($l) => [
            'product_id' => $l->product_id,
            'product_name' => $l->product_name,
            'quantity' => (float) $l->quantity,
            'unit_price' => (float) $l->unit_price,
            'subtotal' => (float) $l->subtotal,
        ])->toArray();

        return new Sale(
            $model->id,
            (string) $model->shop_id,
            $model->status,
            (float) $model->total_amount,
            $model->currency,
            $model->customer_name,
            $model->notes,
            $lines
        );
    }
}
