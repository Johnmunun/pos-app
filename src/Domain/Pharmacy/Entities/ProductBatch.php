<?php

declare(strict_types=1);

namespace Src\Domain\Pharmacy\Entities;

use DateTimeImmutable;
use InvalidArgumentException;
use Src\Domain\Pharmacy\ValueObjects\BatchNumber;
use Src\Domain\Pharmacy\ValueObjects\ExpirationDate;
use Src\Shared\ValueObjects\Quantity;

/**
 * Entity representing a product batch with expiration tracking.
 * 
 * Each batch represents a specific lot of a product with its own
 * expiration date and quantity tracking.
 */
final class ProductBatch
{
    private string $id;
    private string $shopId;
    private string $productId;
    private BatchNumber $batchNumber;
    private Quantity $quantity;
    private ExpirationDate $expirationDate;
    private ?string $purchaseOrderId;
    private ?string $purchaseOrderLineId;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    private function __construct(
        string $id,
        string $shopId,
        string $productId,
        BatchNumber $batchNumber,
        Quantity $quantity,
        ExpirationDate $expirationDate,
        ?string $purchaseOrderId,
        ?string $purchaseOrderLineId,
        bool $isActive,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt
    ) {
        $this->id = $id;
        $this->shopId = $shopId;
        $this->productId = $productId;
        $this->batchNumber = $batchNumber;
        $this->quantity = $quantity;
        $this->expirationDate = $expirationDate;
        $this->purchaseOrderId = $purchaseOrderId;
        $this->purchaseOrderLineId = $purchaseOrderLineId;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * Create a new product batch.
     */
    public static function create(
        string $id,
        string $shopId,
        string $productId,
        BatchNumber $batchNumber,
        Quantity $quantity,
        ExpirationDate $expirationDate,
        ?string $purchaseOrderId = null,
        ?string $purchaseOrderLineId = null
    ): self {
        if ($quantity->getValue() < 0) {
            throw new InvalidArgumentException('La quantité du lot ne peut pas être négative.');
        }

        return new self(
            $id,
            $shopId,
            $productId,
            $batchNumber,
            $quantity,
            $expirationDate,
            $purchaseOrderId,
            $purchaseOrderLineId,
            true,
            new DateTimeImmutable(),
            null
        );
    }

    /**
     * Reconstitute a batch from persistence.
     */
    public static function reconstitute(
        string $id,
        string $shopId,
        string $productId,
        BatchNumber $batchNumber,
        Quantity $quantity,
        ExpirationDate $expirationDate,
        ?string $purchaseOrderId,
        ?string $purchaseOrderLineId,
        bool $isActive,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt
    ): self {
        return new self(
            $id,
            $shopId,
            $productId,
            $batchNumber,
            $quantity,
            $expirationDate,
            $purchaseOrderId,
            $purchaseOrderLineId,
            $isActive,
            $createdAt,
            $updatedAt
        );
    }

    /**
     * Increase the quantity of this batch.
     */
    public function increaseQuantity(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Le montant à ajouter doit être positif.');
        }

        $newValue = $this->quantity->getValue() + $amount;
        $this->quantity = new Quantity($newValue);
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Decrease the quantity of this batch.
     * 
     * @throws InvalidArgumentException if quantity would become negative
     */
    public function decreaseQuantity(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Le montant à retirer doit être positif.');
        }

        $newValue = $this->quantity->getValue() - $amount;
        
        if ($newValue < 0) {
            throw new InvalidArgumentException(
                sprintf(
                    'Stock insuffisant dans le lot %s. Disponible: %d, Demandé: %d',
                    $this->batchNumber->getValue(),
                    $this->quantity->getValue(),
                    $amount
                )
            );
        }

        $this->quantity = new Quantity($newValue);
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Check if this batch has available stock.
     */
    public function hasStock(): bool
    {
        return $this->quantity->getValue() > 0;
    }

    /**
     * Check if this batch is expired.
     */
    public function isExpired(?DateTimeImmutable $asOf = null): bool
    {
        return $this->expirationDate->isExpired($asOf);
    }

    /**
     * Check if this batch expires within the given days.
     */
    public function expiresWithinDays(int $days, ?DateTimeImmutable $asOf = null): bool
    {
        return $this->expirationDate->expiresWithinDays($days, $asOf);
    }

    /**
     * Get expiration status.
     */
    public function getExpirationStatus(int $warningDays = 30, ?DateTimeImmutable $asOf = null): string
    {
        return $this->expirationDate->getStatus($warningDays, $asOf);
    }

    /**
     * Get days until expiration.
     */
    public function getDaysUntilExpiration(?DateTimeImmutable $asOf = null): int
    {
        return $this->expirationDate->daysUntilExpiration($asOf);
    }

    /**
     * Deactivate this batch (soft delete).
     */
    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Reactivate this batch.
     */
    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters

    public function getId(): string
    {
        return $this->id;
    }

    public function getShopId(): string
    {
        return $this->shopId;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getBatchNumber(): BatchNumber
    {
        return $this->batchNumber;
    }

    public function getQuantity(): Quantity
    {
        return $this->quantity;
    }

    public function getExpirationDate(): ExpirationDate
    {
        return $this->expirationDate;
    }

    public function getPurchaseOrderId(): ?string
    {
        return $this->purchaseOrderId;
    }

    public function getPurchaseOrderLineId(): ?string
    {
        return $this->purchaseOrderLineId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
