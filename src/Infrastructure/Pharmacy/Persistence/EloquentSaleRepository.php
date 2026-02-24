<?php

namespace Src\Infrastructure\Pharmacy\Persistence;

use DateTimeImmutable;
use Src\Domain\Pharmacy\Entities\Sale;
use Src\Domain\Pharmacy\Repositories\SaleRepositoryInterface;
use Src\Infrastructure\Pharmacy\Models\SaleModel;
use Src\Shared\ValueObjects\Money;

class EloquentSaleRepository implements SaleRepositoryInterface
{
    public function save(Sale $sale): void
    {
        $depotId = request()?->session()->get('current_depot_id');
        SaleModel::updateOrCreate(
            ['id' => $sale->getId()],
            [
                'shop_id' => $sale->getShopId(),
                'depot_id' => $depotId ? (int) $depotId : null,
                'customer_id' => $sale->getCustomerId() !== null ? (int) $sale->getCustomerId() : null,
                'cash_register_id' => $sale->getCashRegisterId(),
                'cash_register_session_id' => $sale->getCashRegisterSessionId(),
                'status' => $sale->getStatus(),
                'sale_type' => $sale->getSaleType(),
                'total_amount' => $sale->getTotal()->getAmount(),
                'paid_amount' => $sale->getPaidAmount()->getAmount(),
                'balance_amount' => $sale->getBalance()->getAmount(),
                'currency' => $sale->getCurrency(),
                'created_by' => $sale->getCreatedBy(),
                'completed_at' => $sale->getCompletedAt(),
                'cancelled_at' => $sale->getCancelledAt(),
            ]
        );
    }

    public function findById(string $id): ?Sale
    {
        $model = SaleModel::find($id);

        if (!$model) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    public function findByShop(string $shopId, ?DateTimeImmutable $from = null, ?DateTimeImmutable $to = null): array
    {
        $query = SaleModel::query()->where('shop_id', $shopId);

        if ($from) {
            $query->where('created_at', '>=', $from->setTime(0, 0, 0));
        }

        if ($to) {
            // Inclure toute la journée « au » : fin de journée 23:59:59
            $query->where('created_at', '<=', $to->setTime(23, 59, 59));
        }

        return $query->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (SaleModel $model) => $this->mapToEntity($model))
            ->toArray();
    }

    public function findByCustomer(string $shopId, string $customerId): array
    {
        return SaleModel::query()
            ->where('shop_id', $shopId)
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (SaleModel $model) => $this->mapToEntity($model))
            ->toArray();
    }

    private function mapToEntity(SaleModel $model): Sale
    {
        $currency = $model->currency ?? 'USD';

        $saleType = isset($model->sale_type) && $model->sale_type === \Src\Domain\Pharmacy\Entities\Sale::SALE_TYPE_WHOLESALE
            ? \Src\Domain\Pharmacy\Entities\Sale::SALE_TYPE_WHOLESALE
            : \Src\Domain\Pharmacy\Entities\Sale::SALE_TYPE_RETAIL;

        return new Sale(
            $model->id,
            $model->shop_id,
            $model->customer_id !== null ? (string) $model->customer_id : null,
            $model->status,
            new Money((float) $model->total_amount, $currency),
            new Money((float) $model->paid_amount, $currency),
            new Money((float) $model->balance_amount, $currency),
            $currency,
            (int) $model->created_by,
            new DateTimeImmutable($model->created_at),
            $model->completed_at ? new DateTimeImmutable($model->completed_at) : null,
            $model->cancelled_at ? new DateTimeImmutable($model->cancelled_at) : null,
            isset($model->cash_register_id) ? (int) $model->cash_register_id : null,
            isset($model->cash_register_session_id) ? (int) $model->cash_register_session_id : null,
            $saleType
        );
    }
}

