<?php

namespace Src\Application\Ecommerce\DTO;

/**
 * DTO: OrderItemDTO
 *
 * Data Transfer Object pour un item de commande.
 */
final class OrderItemDTO
{
    /**
     * @param string $productId
     * @param string $productName
     * @param string|null $productSku
     * @param float $quantity
     * @param float $unitPrice
     * @param float $discountAmount
     * @param string|null $productImageUrl
     */
    public function __construct(
        public readonly string $productId,
        public readonly string $productName,
        public readonly ?string $productSku,
        public readonly float $quantity,
        public readonly float $unitPrice,
        public readonly float $discountAmount,
        public readonly ?string $productImageUrl = null
    ) {
    }
}
