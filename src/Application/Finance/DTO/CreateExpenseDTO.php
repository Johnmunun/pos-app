<?php

namespace Src\Application\Finance\DTO;

class CreateExpenseDTO
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $shopId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $category,
        public readonly string $description,
        public readonly int $createdBy,
        public readonly ?string $supplierId = null,
        public readonly ?string $attachmentPath = null,
        public readonly ?string $depotId = null,
    ) {}
}
