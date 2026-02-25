<?php

namespace Src\Domain\Finance\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Src\Shared\ValueObjects\Money;
use Src\Domain\Finance\ValueObjects\ExpenseCategory;

/**
 * Aggregate Root : Dépense.
 * Enregistrement d'une sortie d'argent avec catégorie, fournisseur optionnel, pièce justificative.
 */
class Expense
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    private string $id;
    private string $tenantId;
    private string $shopId;
    private ?string $depotId;
    private Money $amount;
    private ExpenseCategory $category;
    private string $description;
    private ?string $supplierId;
    private ?string $attachmentPath;
    private string $status;
    private int $createdBy;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $paidAt;

    public function __construct(
        string $id,
        string $tenantId,
        string $shopId,
        Money $amount,
        ExpenseCategory $category,
        string $description,
        ?string $supplierId = null,
        ?string $attachmentPath = null,
        string $status = self::STATUS_PENDING,
        int $createdBy = 0,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        ?string $depotId = null,
        ?DateTimeImmutable $paidAt = null
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->shopId = $shopId;
        $this->depotId = $depotId;
        $this->amount = $amount;
        $this->category = $category;
        $this->description = $description;
        $this->supplierId = $supplierId;
        $this->attachmentPath = $attachmentPath;
        $this->status = $status;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
        $this->paidAt = $paidAt;
    }

    public static function create(
        string $tenantId,
        string $shopId,
        Money $amount,
        ExpenseCategory $category,
        string $description,
        int $createdBy,
        ?string $supplierId = null,
        ?string $attachmentPath = null,
        ?string $depotId = null
    ): self {
        $id = Uuid::uuid4()->toString();
        return new self(
            $id,
            $tenantId,
            $shopId,
            $amount,
            $category,
            $description,
            $supplierId,
            $attachmentPath,
            self::STATUS_PENDING,
            $createdBy,
            null,
            null,
            $depotId,
            null
        );
    }

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getShopId(): string { return $this->shopId; }
    public function getDepotId(): ?string { return $this->depotId; }
    public function getAmount(): Money { return $this->amount; }
    public function getCategory(): ExpenseCategory { return $this->category; }
    public function getDescription(): string { return $this->description; }
    public function getSupplierId(): ?string { return $this->supplierId; }
    public function getAttachmentPath(): ?string { return $this->attachmentPath; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }
    public function getPaidAt(): ?DateTimeImmutable { return $this->paidAt; }

    public function approve(): void
    {
        if ($this->status === self::STATUS_REJECTED) {
            throw new \DomainException('Cannot approve a rejected expense');
        }
        $this->status = self::STATUS_APPROVED;
        $this->paidAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function reject(): void
    {
        $this->status = self::STATUS_REJECTED;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateAttachment(?string $path): void
    {
        $this->attachmentPath = $path;
        $this->updatedAt = new DateTimeImmutable();
    }
}
