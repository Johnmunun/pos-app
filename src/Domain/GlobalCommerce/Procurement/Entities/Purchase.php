<?php

namespace Src\Domain\GlobalCommerce\Procurement\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * Bon de commande Global Commerce.
 */
class Purchase
{
    private string $id;
    private string $shopId;
    private string $supplierId;
    private string $status;
    private float $totalAmount;
    private string $currency;
    private ?\DateTimeInterface $expectedAt;
    private ?\DateTimeInterface $receivedAt;
    private ?string $notes;
    private DateTimeImmutable $createdAt;
    /** @var array<int, array{product_id: string, product_name: string, ordered_quantity: float, received_quantity: float, unit_cost: float, line_total: float}> */
    private array $lines;

    public function __construct(
        string $id,
        string $shopId,
        string $supplierId,
        string $status,
        float $totalAmount,
        string $currency,
        ?\DateTimeInterface $expectedAt = null,
        ?\DateTimeInterface $receivedAt = null,
        ?string $notes = null,
        array $lines = [],
        ?\DateTimeInterface $createdAt = null
    ) {
        $this->id = $id;
        $this->shopId = $shopId;
        $this->supplierId = $supplierId;
        $this->status = $status;
        $this->totalAmount = $totalAmount;
        $this->currency = $currency;
        $this->expectedAt = $expectedAt;
        $this->receivedAt = $receivedAt;
        $this->notes = $notes;
        $this->lines = $lines;
        $this->createdAt = $createdAt !== null ? DateTimeImmutable::createFromInterface($createdAt) : new DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getShopId(): string { return $this->shopId; }
    public function getSupplierId(): string { return $this->supplierId; }
    public function getStatus(): string { return $this->status; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getCurrency(): string { return $this->currency; }
    public function getExpectedAt(): ?\DateTimeInterface { return $this->expectedAt; }
    public function getReceivedAt(): ?\DateTimeInterface { return $this->receivedAt; }
    public function getNotes(): ?string { return $this->notes; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    /** @return array<int, array{product_id: string, product_name: string, ordered_quantity: float, received_quantity: float, unit_cost: float, line_total: float}> */
    public function getLines(): array { return $this->lines; }

    public function isPending(): bool { return $this->status === 'pending'; }
    public function isReceived(): bool { return $this->status === 'received'; }

    public static function create(
        string $shopId,
        string $supplierId,
        float $totalAmount,
        string $currency,
        array $lines,
        ?\DateTimeInterface $expectedAt = null,
        ?string $notes = null
    ): self {
        return new self(
            Uuid::uuid4()->toString(),
            $shopId,
            $supplierId,
            'pending',
            $totalAmount,
            $currency,
            $expectedAt,
            null,
            $notes,
            $lines
        );
    }
}
