<?php

namespace Src\Application\Pharmacy\DTO;

/**
 * DTO simple pour représenter une ligne de vente côté application.
 * quantity peut être décimale (ex. 0.5 pour demi-plaquette).
 */
class SaleLineDTO
{
    public function __construct(
        public readonly string $productId,
        public readonly int|float $quantity,
        public readonly float $unitPrice,
        public readonly ?float $discountPercent = null
    ) {}
}

