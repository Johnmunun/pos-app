<?php

namespace Src\Domain\GlobalCommerce\Sales\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * Vente Global Commerce.
 */
class Sale
{
    private string $id;
    private string $shopId;
    private string $status;
    private float $totalAmount;
    private string $currency;
    private ?string $customerName;
    private ?string $notes;
    private ?int $createdByUserId;
    private DateTimeImmutable $createdAt;
    /** @var array<int, array{product_id: string, product_name: string, quantity: float, unit_price: float, subtotal: float}> */
    private array $lines;

    public function __construct(
        string $id,
        string $shopId,
        string $status,
        float $totalAmount,
        string $currency,
        ?string $customerName = null,
        ?string $notes = null,
        array $lines = [],
        ?int $createdByUserId = null
    ) {
        $this->id = $id;
        $this->shopId = $shopId;
        $this->status = $status;
        $this->totalAmount = $totalAmount;
        $this->currency = $currency;
        $this->customerName = $customerName;
        $this->notes = $notes;
        $this->lines = $lines;
        $this->createdByUserId = $createdByUserId;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getCreatedByUserId(): ?int { return $this->createdByUserId; }
    public function getShopId(): string { return $this->shopId; }
    public function getStatus(): string { return $this->status; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getCurrency(): string { return $this->currency; }
    public function getCustomerName(): ?string { return $this->customerName; }
    public function getNotes(): ?string { return $this->notes; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    /** @return array<int, array{product_id: string, product_name: string, quantity: float, unit_price: float, subtotal: float}> */
    public function getLines(): array { return $this->lines; }

    public static function create(
        string $shopId,
        float $totalAmount,
        string $currency,
        array $lines,
        ?string $customerName = null,
        ?string $notes = null,
        ?int $createdByUserId = null,
        bool $isDraft = false
    ): self {
        return new self(
            Uuid::uuid4()->toString(),
            $shopId,
            $isDraft ? 'draft' : 'completed',
            $totalAmount,
            $currency,
            $customerName,
            $notes,
            $lines,
            $createdByUserId
        );
    }
}
