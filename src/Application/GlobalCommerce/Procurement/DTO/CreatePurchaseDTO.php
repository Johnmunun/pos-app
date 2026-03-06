<?php

namespace Src\Application\GlobalCommerce\Procurement\DTO;

final class CreatePurchaseDTO
{
    /** @param array<int, array{product_id: string, quantity: float, unit_cost: float}> $lines */
    public function __construct(
        public readonly string $shopId,
        public readonly string $supplierId,
        public readonly array $lines,
        public readonly string $currency,
        public readonly ?string $expectedAt = null,
        public readonly ?string $notes = null
    ) {}
}
