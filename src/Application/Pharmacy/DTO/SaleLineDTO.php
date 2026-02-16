<?php

namespace Src\Application\Pharmacy\DTO;

/**
 * DTO simple pour représenter une ligne de vente côté application.
 */
class SaleLineDTO
{
    public function __construct(
        public readonly string $productId,
        public readonly int $quantity,
        public readonly float $unitPrice,
        public readonly ?float $discountPercent = null
    ) {}
}

