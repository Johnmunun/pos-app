<?php

namespace Src\Application\GlobalCommerce\Inventory\DTO;

final class CreateProductDTO
{
    public function __construct(
        public readonly string $shopId,
        public readonly string $sku,
        public readonly ?string $barcode,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $categoryId,
        public readonly float $purchasePrice,
        public readonly float $salePrice,
        public readonly float $initialStock,
        public readonly float $minimumStock,
        public readonly string $currency,
        public readonly bool $isWeighted,
        public readonly bool $hasExpiration,
        /** @var list<string>|null Inventaire gc_* (id boutique + éventuellement tenant_id legacy). */
        public readonly ?array $inventoryShopIds = null
    ) {}
}

