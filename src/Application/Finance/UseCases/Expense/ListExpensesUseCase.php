<?php

namespace Src\Application\Finance\UseCases\Expense;

use Src\Domain\Finance\Repositories\ExpenseRepositoryInterface;

final class ListExpensesUseCase
{
    public function __construct(
        private ExpenseRepositoryInterface $expenseRepository
    ) {}

    /**
     * @param array{tenant_id?: string, shop_id?: string, category?: string, status?: string, from?: string, to?: string} $filters
     * @return array{items: \Src\Domain\Finance\Entities\Expense[], total: int}
     */
    public function execute(string $tenantId, int $perPage, int $page, array $filters = []): array
    {
        return $this->expenseRepository->findByTenantPaginated($tenantId, $perPage, $page, $filters);
    }
}
