<?php

namespace Src\Infrastructure\Finance\Persistence;

use Src\Domain\Finance\Entities\DebtSettlement;
use Src\Domain\Finance\Repositories\DebtSettlementRepositoryInterface;
use Src\Infrastructure\Finance\Models\DebtSettlementModel;
use Src\Shared\ValueObjects\Money;

class EloquentDebtSettlementRepository implements DebtSettlementRepositoryInterface
{
    public function save(DebtSettlement $settlement): void
    {
        DebtSettlementModel::updateOrCreate(
            ['id' => $settlement->getId()],
            [
                'debt_id' => $settlement->getDebtId(),
                'amount' => $settlement->getAmount()->getAmount(),
                'currency' => $settlement->getAmount()->getCurrency(),
                'payment_method' => $settlement->getPaymentMethod(),
                'reference' => $settlement->getReference(),
                'recorded_by' => $settlement->getRecordedBy(),
                'paid_at' => $settlement->getPaidAt(),
            ]
        );
    }

    public function findByDebt(string $debtId): array
    {
        return DebtSettlementModel::where('debt_id', $debtId)->orderBy('paid_at')->get()
            ->map(fn ($m) => new DebtSettlement(
                $m->id,
                $m->debt_id,
                new Money((float) $m->amount, $m->currency),
                \DateTimeImmutable::createFromMutable($m->paid_at),
                (int) $m->recorded_by,
                $m->payment_method,
                $m->reference
            ))->all();
    }
}
