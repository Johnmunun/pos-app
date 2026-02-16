<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\DTO;

use DateTimeImmutable;

/**
 * DTO for receiving a purchase order with batch information.
 */
final class ReceivePurchaseOrderDTO
{
    /**
     * @param string $purchaseOrderId
     * @param int $userId
     * @param array<ReceiveLineDTO> $lines Lines with batch info
     */
    public function __construct(
        public readonly string $purchaseOrderId,
        public readonly int $userId,
        public readonly array $lines = []
    ) {}

    /**
     * Create from request data.
     * 
     * @param string $purchaseOrderId
     * @param int $userId
     * @param array<array{
     *     line_id: string,
     *     batch_number: string,
     *     expiration_date: string,
     *     quantity?: int|null
     * }> $linesData
     */
    public static function fromArray(string $purchaseOrderId, int $userId, array $linesData): self
    {
        $lines = array_map(
            fn (array $lineData) => ReceiveLineDTO::fromArray($lineData),
            $linesData
        );

        return new self($purchaseOrderId, $userId, $lines);
    }
}
