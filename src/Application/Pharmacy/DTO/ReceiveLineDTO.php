<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\DTO;

use DateTimeImmutable;

/**
 * DTO for a single line in purchase order reception with batch info.
 */
final class ReceiveLineDTO
{
    public function __construct(
        public readonly string $lineId,
        public readonly string $batchNumber,
        public readonly DateTimeImmutable $expirationDate,
        public readonly ?int $quantity = null // null = receive full remaining quantity
    ) {}

    /**
     * @param array{
     *     line_id: string,
     *     batch_number: string,
     *     expiration_date: string,
     *     quantity?: int|null
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            lineId: $data['line_id'],
            batchNumber: $data['batch_number'],
            expirationDate: new DateTimeImmutable($data['expiration_date']),
            quantity: isset($data['quantity']) ? (int) $data['quantity'] : null
        );
    }
}
