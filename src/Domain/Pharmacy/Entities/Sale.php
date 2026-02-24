<?php

namespace Src\Domain\Pharmacy\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Src\Shared\ValueObjects\Money;

/**
 * Entité de domaine : Sale
 *
 * Représente une vente (ticket / facture) pour une boutique.
 * La logique de gestion de stock reste gérée par les UseCases d'inventaire.
 */
class Sale
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const SALE_TYPE_RETAIL = 'retail';
    public const SALE_TYPE_WHOLESALE = 'wholesale';

    private string $id;
    private string $shopId;
    private ?string $customerId;
    private ?int $cashRegisterId;
    private ?int $cashRegisterSessionId;
    private string $status;
    private string $saleType;
    private Money $total;
    private Money $paidAmount;
    private Money $balance;
    private string $currency;
    private int $createdBy;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $completedAt;
    private ?DateTimeImmutable $cancelledAt;

    public function __construct(
        string $id,
        string $shopId,
        ?string $customerId,
        string $status,
        Money $total,
        Money $paidAmount,
        Money $balance,
        string $currency,
        int $createdBy,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $completedAt = null,
        ?DateTimeImmutable $cancelledAt = null,
        ?int $cashRegisterId = null,
        ?int $cashRegisterSessionId = null,
        string $saleType = self::SALE_TYPE_RETAIL
    ) {
        $this->id = $id;
        $this->shopId = $shopId;
        $this->customerId = $customerId;
        $this->cashRegisterId = $cashRegisterId;
        $this->cashRegisterSessionId = $cashRegisterSessionId;
        $this->status = $status;
        $this->saleType = $saleType === self::SALE_TYPE_WHOLESALE ? self::SALE_TYPE_WHOLESALE : self::SALE_TYPE_RETAIL;
        $this->total = $total;
        $this->paidAmount = $paidAmount;
        $this->balance = $balance;
        $this->currency = $currency;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
        $this->completedAt = $completedAt;
        $this->cancelledAt = $cancelledAt;
    }

    public static function createDraft(
        string $shopId,
        ?string $customerId,
        string $currency,
        int $createdBy,
        ?int $cashRegisterId = null,
        ?int $cashRegisterSessionId = null,
        string $saleType = self::SALE_TYPE_RETAIL
    ): self {
        $zero = new Money(0, $currency);

        return new self(
            Uuid::uuid4()->toString(),
            $shopId,
            $customerId,
            self::STATUS_DRAFT,
            $zero,
            $zero,
            $zero,
            $currency,
            $createdBy,
            new DateTimeImmutable(),
            null,
            null,
            $cashRegisterId,
            $cashRegisterSessionId,
            $saleType
        );
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getShopId(): string { return $this->shopId; }
    public function getCustomerId(): ?string { return $this->customerId; }
    public function getCashRegisterId(): ?int { return $this->cashRegisterId; }
    public function getCashRegisterSessionId(): ?int { return $this->cashRegisterSessionId; }
    public function getStatus(): string { return $this->status; }
    public function getSaleType(): string { return $this->saleType; }
    public function getTotal(): Money { return $this->total; }
    public function getPaidAmount(): Money { return $this->paidAmount; }
    public function getBalance(): Money { return $this->balance; }
    public function getCurrency(): string { return $this->currency; }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getCompletedAt(): ?DateTimeImmutable { return $this->completedAt; }
    public function getCancelledAt(): ?DateTimeImmutable { return $this->cancelledAt; }

    // Méthodes métier
    public function attachCustomer(string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function updateTotals(Money $total, Money $paidAmount): void
    {
        if ($total->getCurrency() !== $this->currency || $paidAmount->getCurrency() !== $this->currency) {
            throw new \InvalidArgumentException('Currency mismatch for sale totals');
        }

        $this->total = $total;
        $this->paidAmount = $paidAmount;
        // Solde = montant restant dû (0 si payé intégralement ou trop perçu)
        $balanceAmount = max(0, $total->getAmount() - $paidAmount->getAmount());
        $this->balance = new Money($balanceAmount, $this->currency);
    }

    public function markCompleted(): void
    {
        if ($this->status === self::STATUS_CANCELLED) {
            throw new \LogicException('Cannot complete a cancelled sale');
        }

        $this->status = self::STATUS_COMPLETED;
        $this->completedAt = new DateTimeImmutable();
    }

    public function cancel(): void
    {
        if ($this->status === self::STATUS_COMPLETED) {
            throw new \LogicException('Cannot cancel a completed sale');
        }

        $this->status = self::STATUS_CANCELLED;
        $this->cancelledAt = new DateTimeImmutable();
    }
}

