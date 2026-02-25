<?php

namespace Src\Domain\Finance\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Src\Shared\ValueObjects\Money;

/**
 * Entité : Règlement d'une dette (historique des paiements partiels).
 */
class DebtSettlement
{
    private string $id;
    private string $debtId;
    private Money $amount;
    private DateTimeImmutable $paidAt;
    private ?string $paymentMethod;
    private ?string $reference;
    private int $recordedBy;

    public function __construct(
        string $id,
        string $debtId,
        Money $amount,
        DateTimeImmutable $paidAt,
        int $recordedBy,
        ?string $paymentMethod = null,
        ?string $reference = null
    ) {
        $this->id = $id;
        $this->debtId = $debtId;
        $this->amount = $amount;
        $this->paidAt = $paidAt;
        $this->paymentMethod = $paymentMethod;
        $this->reference = $reference;
        $this->recordedBy = $recordedBy;
    }

    public static function record(
        string $debtId,
        Money $amount,
        int $recordedBy,
        ?string $paymentMethod = null,
        ?string $reference = null
    ): self {
        return new self(
            Uuid::uuid4()->toString(),
            $debtId,
            $amount,
            new DateTimeImmutable(),
            $recordedBy,
            $paymentMethod,
            $reference
        );
    }

    public function getId(): string { return $this->id; }
    public function getDebtId(): string { return $this->debtId; }
    public function getAmount(): Money { return $this->amount; }
    public function getPaidAt(): DateTimeImmutable { return $this->paidAt; }
    public function getPaymentMethod(): ?string { return $this->paymentMethod; }
    public function getReference(): ?string { return $this->reference; }
    public function getRecordedBy(): int { return $this->recordedBy; }
}
