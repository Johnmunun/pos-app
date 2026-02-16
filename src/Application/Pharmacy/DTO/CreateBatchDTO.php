<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\DTO;

use DateTimeImmutable;

/**
 * DTO for creating a new product batch.
 */
final class CreateBatchDTO
{
    public function __construct(
        public readonly string $shopId,
        public readonly string $productId,
        public readonly string $batchNumber,
        public readonly int $quantity,
        public readonly DateTimeImmutable $expirationDate,
        public readonly ?string $purchaseOrderId = null,
        public readonly ?string $purchaseOrderLineId = null
    ) {}

    /**
     * Create from array data.
     * 
     * @param array{
     *     shop_id: string,
     *     product_id: string,
     *     batch_number: string,
     *     quantity: int,
     *     expiration_date: string|DateTimeImmutable,
     *     purchase_order_id?: string|null,
     *     purchase_order_line_id?: string|null
     * } $data
     */
    public static function fromArray(array $data): self
    {
        $expirationDate = $data['expiration_date'];
        if (is_string($expirationDate)) {
            $expirationDate = new DateTimeImmutable($expirationDate);
        }

        return new self(
            shopId: $data['shop_id'],
            productId: $data['product_id'],
            batchNumber: $data['batch_number'],
            quantity: (int) $data['quantity'],
            expirationDate: $expirationDate,
            purchaseOrderId: $data['purchase_order_id'] ?? null,
            purchaseOrderLineId: $data['purchase_order_line_id'] ?? null
        );
    }
}
