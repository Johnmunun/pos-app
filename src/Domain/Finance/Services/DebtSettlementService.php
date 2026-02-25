<?php

namespace Src\Domain\Finance\Services;

use Src\Domain\Finance\Entities\Debt;
use Src\Domain\Finance\Entities\DebtSettlement;
use Src\Domain\Finance\Repositories\DebtRepositoryInterface;
use Src\Domain\Finance\Repositories\DebtSettlementRepositoryInterface;
use Src\Shared\ValueObjects\Money;

/**
 * Domain Service : enregistrement d'un règlement (paiement partiel) sur une dette.
 * Vérification permissions côté Application ; ici uniquement règles métier.
 */
final class DebtSettlementService
{
    public function __construct(
        private DebtRepositoryInterface $debtRepository,
        private DebtSettlementRepositoryInterface $settlementRepository
    ) {}

    public function settle(Debt $debt, Money $amount, int $recordedBy, ?string $paymentMethod = null, ?string $reference = null): DebtSettlement
    {
        if ($debt->getStatus() === Debt::STATUS_SETTLED) {
            throw new \DomainException('Debt is already settled');
        }
        $balance = $debt->getBalance();
        if ($amount->getAmount() > $balance->getAmount()) {
            throw new \DomainException('Payment amount exceeds remaining balance');
        }
        if ($amount->getAmount() <= 0) {
            throw new \DomainException('Payment amount must be positive');
        }

        $debt->recordPayment($amount);
        $this->debtRepository->save($debt);

        $settlement = DebtSettlement::record($debt->getId(), $amount, $recordedBy, $paymentMethod, $reference);
        $this->settlementRepository->save($settlement);

        return $settlement;
    }
}
