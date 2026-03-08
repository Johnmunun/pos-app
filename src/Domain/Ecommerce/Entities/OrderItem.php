<?php

namespace Src\Domain\Ecommerce\Entities;

use DateTimeImmutable;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;

/**
 * Entité de domaine : OrderItem
 *
 * Représente un item dans une commande ecommerce.
 */
class OrderItem
{
    private string $id;
    private string $orderId;
    private string $productId;
    private string $productName;
    private ?string $productSku;
    private Quantity $quantity;
    private Money $unitPrice;
    private Money $discountAmount;
    private Money $subtotal;
    private ?string $productImageUrl;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $orderId,
        string $productId,
        string $productName,
        ?string $productSku,
        Quantity $quantity,
        Money $unitPrice,
        Money $discountAmount,
        Money $subtotal,
        ?string $productImageUrl,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ) {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->productId = $productId;
        $this->productName = $productName;
        $this->productSku = $productSku;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->discountAmount = $discountAmount;
        $this->subtotal = $subtotal;
        $this->productImageUrl = $productImageUrl;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getOrderId(): string { return $this->orderId; }
    public function getProductId(): string { return $this->productId; }
    public function getProductName(): string { return $this->productName; }
    public function getProductSku(): ?string { return $this->productSku; }
    public function getQuantity(): Quantity { return $this->quantity; }
    public function getUnitPrice(): Money { return $this->unitPrice; }
    public function getDiscountAmount(): Money { return $this->discountAmount; }
    public function getSubtotal(): Money { return $this->subtotal; }
    public function getProductImageUrl(): ?string { return $this->productImageUrl; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    // Méthodes métier
    public function updateQuantity(Quantity $newQuantity, Money $unitPrice, Money $discountAmount): void
    {
        if ($unitPrice->getCurrency() !== $this->unitPrice->getCurrency() || 
            $discountAmount->getCurrency() !== $this->discountAmount->getCurrency()) {
            throw new \InvalidArgumentException('Currency mismatch');
        }

        $this->quantity = $newQuantity;
        $this->unitPrice = $unitPrice;
        $this->discountAmount = $discountAmount;
        
        // Recalculer le subtotal
        $lineTotal = $unitPrice->multiply($newQuantity->getValue());
        $this->subtotal = $lineTotal->subtract($discountAmount);
        $this->updatedAt = new DateTimeImmutable();
    }
}
