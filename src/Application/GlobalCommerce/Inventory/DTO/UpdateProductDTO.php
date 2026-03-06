<?php

namespace Src\Application\GlobalCommerce\Inventory\DTO;

final class UpdateProductDTO
{
    public function __construct(
        public readonly string $productId,
        public readonly string $shopId,
        public readonly string $sku,
        public readonly ?string $barcode,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $categoryId,
        public readonly float $purchasePrice,
        public readonly float $salePrice,
        public readonly float $minimumStock,
        public readonly string $currency,
        public readonly bool $isWeighted,
        public readonly bool $hasExpiration,
        public readonly bool $isActive
    ) {}
}
