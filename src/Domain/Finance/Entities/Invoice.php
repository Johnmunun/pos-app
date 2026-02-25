<?php

namespace Src\Domain\Finance\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Src\Shared\ValueObjects\Money;

/**
 * Aggregate Root : Facture.
 * Génération depuis Module Achat ou Module Caisse. Numérotation automatique sécurisée.
 */
class Invoice
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_PAID = 'paid';

    public const SOURCE_SALE = 'sale';
    public const SOURCE_PURCHASE = 'purchase';

    private string $id;
    private string $tenantId;
    private string $shopId;
    private string $number;
    private string $sourceType;
    private string $sourceId;
    private Money $totalAmount;
    private Money $paidAmount;
    private string $currency;
    private string $status;
    private DateTimeImmutable $issuedAt;
    private ?DateTimeImmutable $validatedAt;
    private ?DateTimeImmutable $paidAt;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $tenantId,
        string $shopId,
        string $number,
        string $sourceType,
        string $sourceId,
        Money $totalAmount,
        Money $paidAmount,
        string $status,
        DateTimeImmutable $issuedAt,
        ?DateTimeImmutable $validatedAt = null,
        ?DateTimeImmutable $paidAt = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->shopId = $shopId;
        $this->number = $number;
        $this->sourceType = $sourceType;
        $this->sourceId = $sourceId;
        $this->totalAmount = $totalAmount;
        $this->paidAmount = $paidAmount;
        $this->currency = $totalAmount->getCurrency();
        $this->status = $status;
        $this->issuedAt = $issuedAt;
        $this->validatedAt = $validatedAt;
        $this->paidAt = $paidAt;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getShopId(): string { return $this->shopId; }
    public function getNumber(): string { return $this->number; }
    public function getSourceType(): string { return $this->sourceType; }
    public function getSourceId(): string { return $this->sourceId; }
    public function getTotalAmount(): Money { return $this->totalAmount; }
    public function getPaidAmount(): Money { return $this->paidAmount; }
    public function getCurrency(): string { return $this->currency; }
    public function getStatus(): string { return $this->status; }
    public function getIssuedAt(): DateTimeImmutable { return $this->issuedAt; }
    public function getValidatedAt(): ?DateTimeImmutable { return $this->validatedAt; }
    public function getPaidAt(): ?DateTimeImmutable { return $this->paidAt; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    public function validate(): void
    {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new \DomainException('Only draft invoices can be validated');
        }
        $this->status = self::STATUS_VALIDATED;
        $this->validatedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markPaid(): void
    {
        if ($this->status === self::STATUS_DRAFT) {
            throw new \DomainException('Validate invoice before marking as paid');
        }
        $this->status = self::STATUS_PAID;
        $this->paidAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }
}
