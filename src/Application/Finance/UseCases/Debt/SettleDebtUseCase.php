<?php

namespace Src\Application\Finance\UseCases\Debt;

use Src\Domain\Finance\Entities\DebtSettlement;
use Src\Domain\Finance\Repositories\DebtRepositoryInterface;
use Src\Domain\Finance\Services\DebtSettlementService;
use Src\Shared\ValueObjects\Money;

final class SettleDebtUseCase
{
    public function __construct(
        private DebtRepositoryInterface $debtRepository,
        private DebtSettlementService $settlementService
    ) {}

    public function execute(
        string $debtId,
        float $amount,
        string $currency,
        int $recordedBy,
        ?string $paymentMethod = null,
        ?string $reference = null
    ): DebtSettlement {
        $debt = $this->debtRepository->findById($debtId);
        if (!$debt) {
            throw new \InvalidArgumentException('Debt not found');
        }
        $payment = new Money($amount, $currency);
        return $this->settlementService->settle($debt, $payment, $recordedBy, $paymentMethod, $reference);
    }
}
