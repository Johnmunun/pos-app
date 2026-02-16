<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\DTO;

/**
 * DTO for decreasing stock from batches using FIFO.
 */
final class DecreaseBatchDTO
{
    public function __construct(
        public readonly string $shopId,
        public readonly string $productId,
        public readonly int $quantity,
        public readonly bool $blockIfExpired = true,
        public readonly ?string $saleId = null,
        public readonly ?string $reason = null
    ) {}

    /**
     * Create from array data.
     * 
     * @param array{
     *     shop_id: string,
     *     product_id: string,
     *     quantity: int,
     *     block_if_expired?: bool,
     *     sale_id?: string|null,
     *     reason?: string|null
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            shopId: $data['shop_id'],
            productId: $data['product_id'],
            quantity: (int) $data['quantity'],
            blockIfExpired: $data['block_if_expired'] ?? true,
            saleId: $data['sale_id'] ?? null,
            reason: $data['reason'] ?? null
        );
    }
}
