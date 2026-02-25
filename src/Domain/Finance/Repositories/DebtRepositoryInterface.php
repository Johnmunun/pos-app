<?php

namespace Src\Domain\Finance\Repositories;

use Src\Domain\Finance\Entities\Debt;

interface DebtRepositoryInterface
{
    public function save(Debt $debt): void;

    public function findById(string $id): ?Debt;

    /** @return Debt[] */
    public function findByShop(string $shopId, ?string $type = null, ?string $status = null): array;

    /**
     * @param array{type?: string, status?: string, party_id?: string, from?: string, to?: string} $filters
     * @return array{items: Debt[], total: int}
     */
    public function findByTenantPaginated(string $tenantId, int $perPage, int $page, array $filters = []): array;
}
