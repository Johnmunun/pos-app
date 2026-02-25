<?php

namespace Src\Domain\Finance\Repositories;

use Src\Domain\Finance\Entities\Expense;

interface ExpenseRepositoryInterface
{
    public function save(Expense $expense): void;

    public function findById(string $id): ?Expense;

    /**
     * @param array{tenant_id?: string, shop_id?: string, category?: string, status?: string, from?: string, to?: string} $filters
     * @return array{items: Expense[], total: int}
     */
    public function findByTenantPaginated(string $tenantId, int $perPage, int $page, array $filters = []): array;

    public function delete(string $id): void;
}
