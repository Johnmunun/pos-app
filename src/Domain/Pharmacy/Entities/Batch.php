<?php

namespace Src\Domain\Pharmacy\Entities;

use Src\Domain\Pharmacy\ValueObjects\ExpiryDate;
use Src\Shared\ValueObjects\Quantity;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

class Batch
{
    private string $id;
    private string $shopId;
    private string $productId;
    private string $batchNumber;
    private ExpiryDate $expiryDate;
    private Quantity $quantity;
    private Quantity $initialQuantity;
    private ?string $supplierId;
    private ?string $purchaseOrderId;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $shopId,
        string $productId,
        string $batchNumber,
        ExpiryDate $expiryDate,
        Quantity $quantity,
        ?Quantity $initialQuantity = null,
        ?string $supplierId = null,
        ?string $purchaseOrderId = null
    ) {
        $this->id = $id;
        $this->shopId = $shopId;
        $this->productId = $productId;
        $this->batchNumber = $batchNumber;
        $this->expiryDate = $expiryDate;
        $this->quantity = $quantity;
        $this->initialQuantity = $initialQuantity ?? $quantity;
        $this->supplierId = $supplierId;
        $this->purchaseOrderId = $purchaseOrderId;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getShopId(): string { return $this->shopId; }
    public function getProductId(): string { return $this->productId; }
    public function getBatchNumber(): string { return $this->batchNumber; }
    public function getExpiryDate(): ExpiryDate { return $this->expiryDate; }
    public function getQuantity(): Quantity { return $this->quantity; }
    public function getInitialQuantity(): Quantity { return $this->initialQuantity; }
    public function getSupplierId(): ?string { return $this->supplierId; }
    public function getPurchaseOrderId(): ?string { return $this->purchaseOrderId; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    // Business methods
    public function consume(Quantity $quantity): void
    {
        $this->quantity = $this->quantity->subtract($quantity);
        $this->updatedAt = new DateTimeImmutable();
    }

    public function addStock(Quantity $quantity): void
    {
        $this->quantity = $this->quantity->add($quantity);
        $this->initialQuantity = $this->initialQuantity->add($quantity);
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isExpired(): bool
    {
        return $this->expiryDate->isExpired();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiryDate->isExpiringSoon($days);
    }

    public function getDaysUntilExpiry(): int
    {
        return $this->expiryDate->getDaysUntilExpiry();
    }

    public function isLowStock(int $threshold = 10): bool
    {
        return $this->quantity->getValue() <= $threshold;
    }

    public function isInStock(): bool
    {
        return !$this->quantity->isZero();
    }

    public function getConsumedQuantity(): Quantity
    {
        return $this->initialQuantity->subtract($this->quantity);
    }

    public function getStockPercentage(): float
    {
        if ($this->initialQuantity->isZero()) {
            return 0;
        }
        
        return ($this->quantity->getValue() / $this->initialQuantity->getValue()) * 100;
    }

    // Static factory method
    public static function create(
        string $shopId,
        string $productId,
        string $batchNumber,
        ExpiryDate $expiryDate,
        Quantity $quantity,
        ?string $supplierId = null,
        ?string $purchaseOrderId = null
    ): self {
        return new self(
            Uuid::uuid4()->toString(),
            $shopId,
            $productId,
            $batchNumber,
            $expiryDate,
            $quantity,
            null,
            $supplierId,
            $purchaseOrderId
        );
    }
}