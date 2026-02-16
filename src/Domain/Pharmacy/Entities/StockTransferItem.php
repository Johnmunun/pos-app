<?php

namespace Src\Domain\Pharmacy\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * Entité Domain : StockTransferItem
 *
 * Représente une ligne de produit dans un transfert de stock.
 */
class StockTransferItem
{
    private string $id;
    private string $stockTransferId;
    private string $productId;
    private int $quantity;
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $stockTransferId,
        string $productId,
        int $quantity,
        DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->stockTransferId = $stockTransferId;
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->createdAt = $createdAt;
    }

    /**
     * Crée un nouvel item de transfert
     */
    public static function create(
        string $stockTransferId,
        string $productId,
        int $quantity
    ): self {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('La quantité doit être supérieure à zéro');
        }

        return new self(
            Uuid::uuid4()->toString(),
            $stockTransferId,
            $productId,
            $quantity,
            new DateTimeImmutable()
        );
    }

    /**
     * Met à jour la quantité
     */
    public function updateQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('La quantité doit être supérieure à zéro');
        }

        $this->quantity = $quantity;
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getStockTransferId(): string
    {
        return $this->stockTransferId;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
