<?php

namespace Src\Domain\Pharmacy\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Src\Shared\ValueObjects\Money;

/**
 * Entité de domaine : PurchaseOrder
 *
 * Bon de commande fournisseur.
 */
class PurchaseOrder
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_PARTIALLY_RECEIVED = 'PARTIALLY_RECEIVED';
    public const STATUS_RECEIVED = 'RECEIVED';
    public const STATUS_CANCELLED = 'CANCELLED';

    private string $id;
    private string $shopId;
    private string $supplierId;
    private string $status;
    private Money $total;
    private string $currency;
    private ?DateTimeImmutable $orderedAt;
    private ?DateTimeImmutable $expectedAt;
    private ?DateTimeImmutable $receivedAt;
    private int $createdBy;
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $shopId,
        string $supplierId,
        string $status,
        Money $total,
        string $currency,
        ?DateTimeImmutable $orderedAt,
        ?DateTimeImmutable $expectedAt,
        ?DateTimeImmutable $receivedAt,
        int $createdBy,
        DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->shopId = $shopId;
        $this->supplierId = $supplierId;
        $this->status = $status;
        $this->total = $total;
        $this->currency = $currency;
        $this->orderedAt = $orderedAt;
        $this->expectedAt = $expectedAt;
        $this->receivedAt = $receivedAt;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
    }

    public static function createDraft(
        string $shopId,
        string $supplierId,
        string $currency,
        ?DateTimeImmutable $expectedAt,
        int $createdBy
    ): self {
        $zero = new Money(0, $currency);

        return new self(
            Uuid::uuid4()->toString(),
            $shopId,
            $supplierId,
            self::STATUS_DRAFT,
            $zero,
            $currency,
            null,
            $expectedAt,
            null,
            $createdBy,
            new DateTimeImmutable()
        );
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getShopId(): string { return $this->shopId; }
    public function getSupplierId(): string { return $this->supplierId; }
    public function getStatus(): string { return $this->status; }
    public function getTotal(): Money { return $this->total; }
    public function getCurrency(): string { return $this->currency; }
    public function getOrderedAt(): ?DateTimeImmutable { return $this->orderedAt; }
    public function getExpectedAt(): ?DateTimeImmutable { return $this->expectedAt; }
    public function getReceivedAt(): ?DateTimeImmutable { return $this->receivedAt; }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }

    // Méthodes métier
    public function confirm(): void
    {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new \LogicException('Only draft purchase orders can be confirmed');
        }

        $this->status = self::STATUS_CONFIRMED;
        $this->orderedAt = new DateTimeImmutable();
    }

    public function markPartiallyReceived(): void
    {
        if (!in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_PARTIALLY_RECEIVED], true)) {
            throw new \LogicException('Cannot mark as partially received from current status');
        }

        $this->status = self::STATUS_PARTIALLY_RECEIVED;
    }

    public function markReceived(): void
    {
        if (!in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_PARTIALLY_RECEIVED], true)) {
            throw new \LogicException('Cannot mark as received from current status');
        }

        $this->status = self::STATUS_RECEIVED;
        $this->receivedAt = new DateTimeImmutable();
    }

    public function cancel(): void
    {
        if (in_array($this->status, [self::STATUS_RECEIVED, self::STATUS_PARTIALLY_RECEIVED], true)) {
            throw new \LogicException('Cannot cancel a purchase order that has received items');
        }

        $this->status = self::STATUS_CANCELLED;
    }

    public function updateTotal(Money $total): void
    {
        if ($total->getCurrency() !== $this->currency) {
            throw new \InvalidArgumentException('Currency mismatch for purchase order total');
        }

        $this->total = $total;
    }
}

