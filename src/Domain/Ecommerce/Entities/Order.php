<?php

namespace Src\Domain\Ecommerce\Entities;

use DateTimeImmutable;
use Src\Shared\ValueObjects\Money;

/**
 * Entité de domaine : Order
 *
 * Représente une commande ecommerce.
 */
class Order
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_FAILED = 'failed';
    public const PAYMENT_STATUS_REFUNDED = 'refunded';

    private string $id;
    private string $shopId;
    private string $orderNumber;
    private string $status;
    private string $customerName;
    private string $customerEmail;
    private ?string $customerPhone;
    private string $shippingAddress;
    private ?string $billingAddress;
    private Money $subtotal;
    private Money $shippingAmount;
    private Money $taxAmount;
    private Money $discountAmount;
    private Money $total;
    private string $currency;
    private ?string $paymentMethod;
    private string $paymentStatus;
    private ?string $notes;
    private ?DateTimeImmutable $confirmedAt;
    private ?DateTimeImmutable $shippedAt;
    private ?DateTimeImmutable $deliveredAt;
    private ?DateTimeImmutable $cancelledAt;
    private ?int $createdBy;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $shopId,
        string $orderNumber,
        string $status,
        string $customerName,
        string $customerEmail,
        ?string $customerPhone,
        string $shippingAddress,
        ?string $billingAddress,
        Money $subtotal,
        Money $shippingAmount,
        Money $taxAmount,
        Money $discountAmount,
        Money $total,
        string $currency,
        ?string $paymentMethod,
        string $paymentStatus,
        ?string $notes,
        ?int $createdBy,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $confirmedAt = null,
        ?DateTimeImmutable $shippedAt = null,
        ?DateTimeImmutable $deliveredAt = null,
        ?DateTimeImmutable $cancelledAt = null
    ) {
        $this->id = $id;
        $this->shopId = $shopId;
        $this->orderNumber = $orderNumber;
        $this->status = $status;
        $this->customerName = $customerName;
        $this->customerEmail = $customerEmail;
        $this->customerPhone = $customerPhone;
        $this->shippingAddress = $shippingAddress;
        $this->billingAddress = $billingAddress;
        $this->subtotal = $subtotal;
        $this->shippingAmount = $shippingAmount;
        $this->taxAmount = $taxAmount;
        $this->discountAmount = $discountAmount;
        $this->total = $total;
        $this->currency = $currency;
        $this->paymentMethod = $paymentMethod;
        $this->paymentStatus = $paymentStatus;
        $this->notes = $notes;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->confirmedAt = $confirmedAt;
        $this->shippedAt = $shippedAt;
        $this->deliveredAt = $deliveredAt;
        $this->cancelledAt = $cancelledAt;
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getShopId(): string { return $this->shopId; }
    public function getOrderNumber(): string { return $this->orderNumber; }
    public function getStatus(): string { return $this->status; }
    public function getCustomerName(): string { return $this->customerName; }
    public function getCustomerEmail(): string { return $this->customerEmail; }
    public function getCustomerPhone(): ?string { return $this->customerPhone; }
    public function getShippingAddress(): string { return $this->shippingAddress; }
    public function getBillingAddress(): ?string { return $this->billingAddress; }
    public function getSubtotal(): Money { return $this->subtotal; }
    public function getShippingAmount(): Money { return $this->shippingAmount; }
    public function getTaxAmount(): Money { return $this->taxAmount; }
    public function getDiscountAmount(): Money { return $this->discountAmount; }
    public function getTotal(): Money { return $this->total; }
    public function getCurrency(): string { return $this->currency; }
    public function getPaymentMethod(): ?string { return $this->paymentMethod; }
    public function getPaymentStatus(): string { return $this->paymentStatus; }
    public function getNotes(): ?string { return $this->notes; }
    public function getConfirmedAt(): ?DateTimeImmutable { return $this->confirmedAt; }
    public function getShippedAt(): ?DateTimeImmutable { return $this->shippedAt; }
    public function getDeliveredAt(): ?DateTimeImmutable { return $this->deliveredAt; }
    public function getCancelledAt(): ?DateTimeImmutable { return $this->cancelledAt; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    // Méthodes métier
    public function confirm(): void
    {
        if ($this->status === self::STATUS_CANCELLED) {
            throw new \LogicException('Cannot confirm a cancelled order');
        }
        if ($this->status === self::STATUS_DELIVERED) {
            throw new \LogicException('Cannot confirm a delivered order');
        }

        $this->status = self::STATUS_CONFIRMED;
        $this->confirmedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markAsProcessing(): void
    {
        if ($this->status === self::STATUS_CANCELLED) {
            throw new \LogicException('Cannot process a cancelled order');
        }
        if ($this->status === self::STATUS_PENDING) {
            throw new \LogicException('Order must be confirmed before processing');
        }

        $this->status = self::STATUS_PROCESSING;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markAsShipped(): void
    {
        if ($this->status === self::STATUS_CANCELLED) {
            throw new \LogicException('Cannot ship a cancelled order');
        }
        if (!in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_PROCESSING])) {
            throw new \LogicException('Order must be confirmed or processing before shipping');
        }

        $this->status = self::STATUS_SHIPPED;
        $this->shippedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markAsDelivered(): void
    {
        if ($this->status === self::STATUS_CANCELLED) {
            throw new \LogicException('Cannot deliver a cancelled order');
        }
        if ($this->status !== self::STATUS_SHIPPED) {
            throw new \LogicException('Order must be shipped before delivery');
        }

        $this->status = self::STATUS_DELIVERED;
        $this->deliveredAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function cancel(): void
    {
        if (in_array($this->status, [self::STATUS_SHIPPED, self::STATUS_DELIVERED])) {
            throw new \LogicException('Cannot cancel a shipped or delivered order');
        }

        $this->status = self::STATUS_CANCELLED;
        $this->cancelledAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markPaymentAsPaid(): void
    {
        $this->paymentStatus = self::PAYMENT_STATUS_PAID;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markPaymentAsFailed(): void
    {
        $this->paymentStatus = self::PAYMENT_STATUS_FAILED;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markPaymentAsRefunded(): void
    {
        $this->paymentStatus = self::PAYMENT_STATUS_REFUNDED;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateTotals(Money $subtotal, Money $shippingAmount, Money $taxAmount, Money $discountAmount): void
    {
        if ($subtotal->getCurrency() !== $this->currency || 
            $shippingAmount->getCurrency() !== $this->currency ||
            $taxAmount->getCurrency() !== $this->currency ||
            $discountAmount->getCurrency() !== $this->currency) {
            throw new \InvalidArgumentException('Currency mismatch for order totals');
        }

        $this->subtotal = $subtotal;
        $this->shippingAmount = $shippingAmount;
        $this->taxAmount = $taxAmount;
        $this->discountAmount = $discountAmount;
        
        // Total = subtotal + shipping + tax - discount
        $totalAmount = $subtotal->getAmount() + $shippingAmount->getAmount() + $taxAmount->getAmount() - $discountAmount->getAmount();
        $this->total = new Money(max(0, $totalAmount), $this->currency);
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateNotes(?string $notes): void
    {
        $this->notes = $notes;
        $this->updatedAt = new DateTimeImmutable();
    }
}
