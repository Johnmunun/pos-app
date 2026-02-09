<?php

namespace Src\Application\Pharmacy\DTO;

use Src\Shared\ValueObjects\Quantity;

class UpdateStockDTO
{
    public function __construct(
        public readonly string $productId,
        public readonly Quantity $quantity,
        public readonly string $type, // 'add' or 'remove'
        public readonly ?string $batchId = null,
        public readonly ?string $reason = null
    ) {}
}