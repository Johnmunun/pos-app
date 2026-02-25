<?php

namespace Src\Domain\Finance\Repositories;

use Src\Domain\Finance\Entities\DebtSettlement;

interface DebtSettlementRepositoryInterface
{
    public function save(DebtSettlement $settlement): void;

    /** @return DebtSettlement[] */
    public function findByDebt(string $debtId): array;
}
