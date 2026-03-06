<?php

namespace Src\Application\GlobalCommerce\Sales\DTO;

final class CreateSaleDTO
{
    /**
     * @param array<int, array{
     *   product_id: string,
     *   quantity: float,
     *   unit_price?: float,
     *   discount_percent?: float|null
     * }> $lines
     */
    public function __construct(
        public readonly string $shopId,
        public readonly array $lines,
        public readonly string $currency,
        public readonly ?string $customerName = null,
        public readonly ?string $notes = null,
        public readonly ?int $createdByUserId = null,
        public readonly bool $isDraft = false
    ) {}
}
