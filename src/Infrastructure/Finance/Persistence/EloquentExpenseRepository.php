<?php

namespace Src\Infrastructure\Finance\Persistence;

use Src\Domain\Finance\Entities\Expense;
use Src\Domain\Finance\Repositories\ExpenseRepositoryInterface;
use Src\Domain\Finance\ValueObjects\ExpenseCategory;
use Src\Infrastructure\Finance\Models\ExpenseModel;
use Src\Shared\ValueObjects\Money;

class EloquentExpenseRepository implements ExpenseRepositoryInterface
{
    public function save(Expense $expense): void
    {
        ExpenseModel::updateOrCreate(
            ['id' => $expense->getId()],
            [
                'tenant_id' => $expense->getTenantId(),
                'shop_id' => $expense->getShopId(),
                'depot_id' => $expense->getDepotId(),
                'amount' => $expense->getAmount()->getAmount(),
                'currency' => $expense->getAmount()->getCurrency(),
                'category' => $expense->getCategory()->getValue(),
                'description' => $expense->getDescription(),
                'supplier_id' => $expense->getSupplierId(),
                'attachment_path' => $expense->getAttachmentPath(),
                'status' => $expense->getStatus(),
                'created_by' => $expense->getCreatedBy(),
                'paid_at' => $expense->getPaidAt(),
            ]
        );
    }

    public function findById(string $id): ?Expense
    {
        $model = ExpenseModel::find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByTenantPaginated(string $tenantId, int $perPage, int $page, array $filters = []): array
    {
        $query = ExpenseModel::where('tenant_id', $tenantId)->orderByDesc('created_at');

        if (!empty($filters['shop_id'])) {
            $query->where('shop_id', $filters['shop_id']);
        }
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
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

    public function delete(string $id): void
    {
        ExpenseModel::destroy($id);
    }

    private function toEntity(ExpenseModel $model): Expense
    {
        return new Expense(
            $model->id,
            (string) $model->tenant_id,
            $model->shop_id,
            new Money((float) $model->amount, $model->currency),
            new ExpenseCategory($model->category),
            $model->description,
            $model->supplier_id,
            $model->attachment_path,
            $model->status,
            (int) $model->created_by,
            \DateTimeImmutable::createFromMutable($model->created_at),
            \DateTimeImmutable::createFromMutable($model->updated_at),
            $model->depot_id ? (string) $model->depot_id : null,
            $model->paid_at ? \DateTimeImmutable::createFromMutable($model->paid_at) : null
        );
    }
}
