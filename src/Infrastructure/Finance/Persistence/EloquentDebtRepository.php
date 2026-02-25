<?php

namespace Src\Infrastructure\Finance\Persistence;

use Src\Domain\Finance\Entities\Debt;
use Src\Domain\Finance\Repositories\DebtRepositoryInterface;
use Src\Infrastructure\Finance\Models\DebtModel;
use Src\Shared\ValueObjects\Money;

class EloquentDebtRepository implements DebtRepositoryInterface
{
    public function save(Debt $debt): void
    {
        DebtModel::updateOrCreate(
            ['id' => $debt->getId()],
            [
                'tenant_id' => $debt->getTenantId(),
                'shop_id' => $debt->getShopId(),
                'type' => $debt->getType(),
                'party_id' => $debt->getPartyId(),
                'total_amount' => $debt->getTotalAmount()->getAmount(),
                'paid_amount' => $debt->getPaidAmount()->getAmount(),
                'currency' => $debt->getCurrency(),
                'reference_type' => $debt->getReferenceType(),
                'reference_id' => $debt->getReferenceId(),
                'status' => $debt->getStatus(),
                'due_date' => $debt->getDueDate(),
                'settled_at' => $debt->getSettledAt(),
            ]
        );
    }

    public function findById(string $id): ?Debt
    {
        $model = DebtModel::find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByShop(string $shopId, ?string $type = null, ?string $status = null): array
    {
        $query = DebtModel::where('shop_id', $shopId)->orderByDesc('created_at');
        if ($type) {
            $query->where('type', $type);
        }
        if ($status) {
            $query->where('status', $status);
        }
        return $query->get()->map(fn ($m) => $this->toEntity($m))->all();
    }

    public function findByTenantPaginated(string $tenantId, int $perPage, int $page, array $filters = []): array
    {
        $query = DebtModel::where('tenant_id', $tenantId)->orderByDesc('created_at');
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['party_id'])) {
            $query->where('party_id', $filters['party_id']);
        }
        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
        $total = $query->count();
        $items = $query->forPage($page, $perPage)->get()->map(fn ($m) => $this->toEntity($m))->all();
        return ['items' => $items, 'total' => $total];
    }

    private function toEntity(DebtModel $model): Debt
    {
        return new Debt(
            $model->id,
            (string) $model->tenant_id,
            $model->shop_id,
            $model->type,
            $model->party_id,
            new Money((float) $model->total_amount, $model->currency),
            new Money((float) $model->paid_amount, $model->currency),
            $model->reference_type,
            $model->reference_id,
            $model->status,
            $model->due_date ? \DateTimeImmutable::createFromMutable($model->due_date) : null,
            \DateTimeImmutable::createFromMutable($model->created_at),
            \DateTimeImmutable::createFromMutable($model->updated_at),
            $model->settled_at ? \DateTimeImmutable::createFromMutable($model->settled_at) : null
        );
    }
}
