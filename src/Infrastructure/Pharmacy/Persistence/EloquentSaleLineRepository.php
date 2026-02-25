<?php

namespace Src\Infrastructure\Pharmacy\Persistence;

use Src\Domain\Pharmacy\Entities\SaleLine;
use Src\Domain\Pharmacy\Repositories\SaleLineRepositoryInterface;
use Src\Infrastructure\Pharmacy\Models\SaleLineModel;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;

class EloquentSaleLineRepository implements SaleLineRepositoryInterface
{
    public function save(SaleLine $line): void
    {
        $currency = $line->getUnitPrice()->getCurrency();

        SaleLineModel::updateOrCreate(
            ['id' => $line->getId()],
            [
                'sale_id' => $line->getSaleId(),
                'product_id' => $line->getProductId(),
                'quantity' => $line->getQuantity()->getValue(),
                'unit_price_amount' => $line->getUnitPrice()->getAmount(),
                'currency' => $currency,
                'line_total_amount' => $line->getLineTotal()->getAmount(),
                'discount_percent' => $line->getDiscountPercent(),
            ]
        );
    }

    public function findBySale(string $saleId): array
    {
        return SaleLineModel::where('sale_id', $saleId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function (SaleLineModel $model) {
                $currency = $model->currency ?? 'USD';

                return new SaleLine(
                    $model->id,
                    $model->sale_id,
                    $model->product_id,
                    new Quantity((float) $model->quantity),
                    new Money((float) $model->unit_price_amount, $currency),
                    new Money((float) $model->line_total_amount, $currency),
                    $model->discount_percent !== null ? (float) $model->discount_percent : null,
                    new \DateTimeImmutable($model->created_at)
                );
            })
            ->toArray();
    }

    public function deleteBySale(string $saleId): void
    {
        SaleLineModel::where('sale_id', $saleId)->delete();
    }
}

