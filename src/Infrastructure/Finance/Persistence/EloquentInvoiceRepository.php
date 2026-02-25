<?php

namespace Src\Infrastructure\Finance\Persistence;

use Src\Domain\Finance\Entities\Invoice;
use Src\Domain\Finance\Repositories\InvoiceRepositoryInterface;
use Src\Infrastructure\Finance\Models\InvoiceModel;
use Src\Shared\ValueObjects\Money;

class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    public function save(Invoice $invoice): void
    {
        InvoiceModel::updateOrCreate(
            ['id' => $invoice->getId()],
            [
                'tenant_id' => $invoice->getTenantId(),
                'shop_id' => $invoice->getShopId(),
                'number' => $invoice->getNumber(),
                'source_type' => $invoice->getSourceType(),
                'source_id' => $invoice->getSourceId(),
                'total_amount' => $invoice->getTotalAmount()->getAmount(),
                'paid_amount' => $invoice->getPaidAmount()->getAmount(),
                'currency' => $invoice->getCurrency(),
                'status' => $invoice->getStatus(),
                'issued_at' => $invoice->getIssuedAt(),
                'validated_at' => $invoice->getValidatedAt(),
                'paid_at' => $invoice->getPaidAt(),
            ]
        );
    }

    public function findById(string $id): ?Invoice
    {
        $model = InvoiceModel::find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByNumber(string $number, string $shopId): ?Invoice
    {
        $model = InvoiceModel::where('shop_id', $shopId)->where('number', $number)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function getLastSequenceForShop(string $shopId, string $prefix, string $year): int
    {
        $pattern = $prefix . '-' . $year . '-%';
        $last = InvoiceModel::where('shop_id', $shopId)->where('number', 'like', $pattern)->orderByDesc('number')->first();
        if (!$last) {
            return 0;
        }
        $parts = explode('-', $last->number);
        $num = (int) end($parts);
        return $num;
    }

    public function findByTenantPaginated(string $tenantId, int $perPage, int $page, array $filters = []): array
    {
        $query = InvoiceModel::where('tenant_id', $tenantId)->orderByDesc('issued_at');
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['source_type'])) {
            $query->where('source_type', $filters['source_type']);
        }
        if (!empty($filters['from'])) {
            $query->whereDate('issued_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('issued_at', '<=', $filters['to']);
        }
        $total = $query->count();
        $items = $query->forPage($page, $perPage)->get()->map(fn ($m) => $this->toEntity($m))->all();
        return ['items' => $items, 'total' => $total];
    }

    private function toEntity(InvoiceModel $model): Invoice
    {
        return new Invoice(
            $model->id,
            (string) $model->tenant_id,
            $model->shop_id,
            $model->number,
            $model->source_type,
            $model->source_id,
            new Money((float) $model->total_amount, $model->currency),
            new Money((float) $model->paid_amount, $model->currency),
            $model->status,
            \DateTimeImmutable::createFromMutable($model->issued_at),
            $model->validated_at ? \DateTimeImmutable::createFromMutable($model->validated_at) : null,
            $model->paid_at ? \DateTimeImmutable::createFromMutable($model->paid_at) : null,
            \DateTimeImmutable::createFromMutable($model->created_at ?? $model->issued_at),
            \DateTimeImmutable::createFromMutable($model->updated_at ?? $model->issued_at)
        );
    }
}
