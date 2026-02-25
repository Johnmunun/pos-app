<?php

namespace Src\Application\Finance\UseCases\Debt;

use Src\Domain\Finance\Entities\Debt;
use Src\Domain\Finance\Repositories\DebtRepositoryInterface;
use Src\Shared\ValueObjects\Money;

final class CreateDebtUseCase
{
    public function __construct(
        private DebtRepositoryInterface $debtRepository
    ) {}

    public function execute(
        string $tenantId,
        string $shopId,
        string $type,
        string $partyId,
        Money $totalAmount,
        Money $paidAmount,
        string $referenceType,
        ?string $referenceId = null,
        ?\DateTimeImmutable $dueDate = null
    ): Debt {
        $debt = Debt::create(
            $tenantId,
            $shopId,
            $type,
            $partyId,
            $totalAmount,
            $paidAmount,
            $referenceType,
            $referenceId,
            $dueDate
        );
        $this->debtRepository->save($debt);
        return $debt;
    }
}
