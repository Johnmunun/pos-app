<?php

namespace Src\Domain\Finance\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Src\Shared\ValueObjects\Money;

/**
 * Aggregate Root : Dette (client ou fournisseur).
 * Création automatique possible si paiement partiel (vente/achat).
 * Règles : interdiction de clôturer si solde ≠ 0.
 */
class Debt
{
    public const TYPE_CLIENT = 'client';
    public const TYPE_SUPPLIER = 'supplier';

    public const STATUS_OPEN = 'open';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_SETTLED = 'settled';
    public const STATUS_OVERDUE = 'overdue';

    private string $id;
    private string $tenantId;
    private string $shopId;
    private string $type;
    private string $partyId;
    private Money $totalAmount;
    private Money $paidAmount;
    private string $currency;
    private string $referenceType;
    private ?string $referenceId;
    private string $status;
    private ?DateTimeImmutable $dueDate;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $settledAt;

    public function __construct(
        string $id,
        string $tenantId,
        string $shopId,
        string $type,
        string $partyId,
        Money $totalAmount,
        Money $paidAmount,
        string $referenceType,
        ?string $referenceId,
        string $status,
        ?DateTimeImmutable $dueDate = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        ?DateTimeImmutable $settledAt = null
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->shopId = $shopId;
        $this->type = $type;
        $this->partyId = $partyId;
        $this->totalAmount = $totalAmount;
        $this->paidAmount = $paidAmount;
        $this->currency = $totalAmount->getCurrency();
        $this->referenceType = $referenceType;
        $this->referenceId = $referenceId;
        $this->status = $status;
        $this->dueDate = $dueDate;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
        $this->settledAt = $settledAt;
    }

    public static function create(
        string $tenantId,
        string $shopId,
        string $type,
        string $partyId,
        Money $totalAmount,
        Money $paidAmount,
        string $referenceType,
        ?string $referenceId = null,
        ?DateTimeImmutable $dueDate = null
    ): self {
        $id = Uuid::uuid4()->toString();
        $balanceAmount = $totalAmount->getAmount() - $paidAmount->getAmount();
        $status = $balanceAmount <= 0 ? self::STATUS_SETTLED : ($paidAmount->getAmount() > 0 ? self::STATUS_PARTIAL : self::STATUS_OPEN);
        $settledAt = $balanceAmount <= 0 ? new DateTimeImmutable() : null;
        return new self(
            $id,
            $tenantId,
            $shopId,
            $type,
            $partyId,
            $totalAmount,
            $paidAmount,
            $referenceType,
            $referenceId,
            $status,
            $dueDate,
            null,
            null,
            $settledAt
        );
    }

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getShopId(): string { return $this->shopId; }
    public function getType(): string { return $this->type; }
    public function getPartyId(): string { return $this->partyId; }
    public function getTotalAmount(): Money { return $this->totalAmount; }
    public function getPaidAmount(): Money { return $this->paidAmount; }
    public function getCurrency(): string { return $this->currency; }
    public function getReferenceType(): string { return $this->referenceType; }
    public function getReferenceId(): ?string { return $this->referenceId; }
    public function getStatus(): string { return $this->status; }
    public function getDueDate(): ?DateTimeImmutable { return $this->dueDate; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }
    public function getSettledAt(): ?DateTimeImmutable { return $this->settledAt; }

    /** Solde restant dû (toujours >= 0). */
    public function getBalance(): Money
    {
        if ($this->paidAmount->getAmount() >= $this->totalAmount->getAmount()) {
            return new Money(0, $this->currency);
        }
        return $this->totalAmount->subtract($this->paidAmount);
    }

    public function canClose(): bool
    {
        return $this->getBalance()->getAmount() === 0.0;
    }

    /** Enregistrer un paiement partiel (appelé par DebtSettlementService). */
    public function recordPayment(Money $amount): void
    {
        if ($this->status === self::STATUS_SETTLED) {
            throw new \DomainException('Cannot pay a settled debt');
        }
        if ($amount->getCurrency() !== $this->currency) {
            throw new \InvalidArgumentException('Currency mismatch');
        }
        $newPaid = $this->paidAmount->add($amount);
        if ($newPaid->getAmount() > $this->totalAmount->getAmount()) {
            throw new \DomainException('Payment exceeds total amount');
        }
        $this->paidAmount = $newPaid;
        $this->updatedAt = new DateTimeImmutable();
        if ($this->getBalance()->getAmount() <= 0) {
            $this->status = self::STATUS_SETTLED;
            $this->settledAt = new DateTimeImmutable();
        } else {
            $this->status = self::STATUS_PARTIAL;
        }
    }

    /** Interdiction de clôturer si solde ≠ 0. */
    public function close(): void
    {
        if (!$this->canClose()) {
            throw new \DomainException('Cannot close debt with non-zero balance');
        }
        $this->status = self::STATUS_SETTLED;
        $this->settledAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }
}
