<?php

namespace Src\Application\Pharmacy\DTO;

class UpdateProductDTO
{
    public function __construct(
        public readonly string $shopId,
        public readonly ?string $name = null,
        public readonly ?string $productCode = null,
        public readonly ?string $description = null,
        public readonly ?string $categoryId = null,
        public readonly ?float $price = null,
        public readonly ?string $currency = null,
        public readonly ?float $cost = null,
        public readonly ?int $minimumStock = null,
        public readonly ?string $unit = null,
        public readonly ?string $medicineType = null,
        public readonly ?string $dosage = null,
        public readonly ?bool $prescriptionRequired = null,
        public readonly ?string $manufacturer = null,
        public readonly ?string $supplierId = null,
        public readonly ?bool $isActive = null
    ) {}
}