<?php

namespace Src\Application\Ecommerce\DTO;

/**
 * DTO: CreateOrderDTO
 *
 * Data Transfer Object pour créer une commande ecommerce.
 */
final class CreateOrderDTO
{
    /**
     * @param string $shopId
     * @param string $customerName
     * @param string $customerEmail
     * @param string|null $customerPhone
     * @param string $shippingAddress
     * @param string|null $billingAddress
     * @param float $subtotalAmount
     * @param float $shippingAmount
     * @param float $taxAmount
     * @param float $discountAmount
     * @param string $currency
     * @param string|null $paymentMethod
     * @param string|null $notes
     * @param string $paymentStatus
     * @param OrderItemDTO[] $items
     * @param int|null $createdBy
     */
    public function __construct(
        public readonly string $shopId,
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly ?string $customerPhone,
        public readonly string $shippingAddress,
        public readonly ?string $billingAddress,
        public readonly float $subtotalAmount,
        public readonly float $shippingAmount,
        public readonly float $taxAmount,
        public readonly float $discountAmount,
        public readonly string $currency,
        public readonly ?string $paymentMethod,
        public readonly ?string $notes,
        public readonly string $paymentStatus,
        public readonly array $items,
        public readonly ?int $createdBy = null
    ) {
    }
}
