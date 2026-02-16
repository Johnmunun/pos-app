<?php

namespace Src\Domain\Pharmacy\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Src\Shared\ValueObjects\Quantity;

/**
 * Entité Domain : StockMovement
 *
 * Représente un mouvement de stock (IN, OUT, ADJUSTMENT) pour un produit.
 * Chaque changement de stock DOIT être tracé par un StockMovement.
 */
class StockMovement
{
    private string $id;
    private string $shopId;
    private string $productId;
    private string $type; // IN, OUT, ADJUSTMENT
    private Quantity $quantity;
    private string $reference;
    private int $createdBy;
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $shopId,
        string $productId,
        string $type,
        Quantity $quantity,
        string $reference,
        int $createdBy,
        DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->shopId = $shopId;
        $this->productId = $productId;
        $this->type = $type;
        $this->quantity = $quantity;
        $this->reference = $reference;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
    }

    public static function in(
        string $shopId,
        string $productId,
        Quantity $quantity,
        string $reference,
        int $createdBy
    ): self {
        return new self(
            Uuid::uuid4()->toString(),
            $shopId,
            $productId,
            'IN',
            $quantity,
            $reference,
            $createdBy,
            new DateTimeImmutable()
        );
    }

    public static function out(
        string $shopId,
        string $productId,
        Quantity $quantity,
        string $reference,
        int $createdBy
    ): self {
        return new self(
            Uuid::uuid4()->toString(),
            $shopId,
            $productId,
            'OUT',
            $quantity,
            $reference,
            $createdBy,
            new DateTimeImmutable()
        );
    }

    public static function adjustment(
        string $shopId,
        string $productId,
        Quantity $quantity,
        string $reference,
        int $createdBy
    ): self {
        return new self(
            Uuid::uuid4()->toString(),
            $shopId,
            $productId,
            'ADJUSTMENT',
            $quantity,
            $reference,
            $createdBy,
            new DateTimeImmutable()
        );
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getShopId(): string { return $this->shopId; }
    public function getProductId(): string { return $this->productId; }
    public function getType(): string { return $this->type; }
    public function getQuantity(): Quantity { return $this->quantity; }
    public function getReference(): string { return $this->reference; }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
}

