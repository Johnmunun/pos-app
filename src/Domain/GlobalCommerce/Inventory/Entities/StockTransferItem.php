<?php

namespace Src\Domain\GlobalCommerce\Inventory\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * Entité Domain : StockTransferItem (GlobalCommerce)
 *
 * Représente une ligne de produit dans un transfert de stock.
 * Quantité en décimal pour supporter les produits pondérés.
 */
class StockTransferItem
{
    private string $id;
    private string $stockTransferId;
    private string $productId;
    private float $quantity;
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $stockTransferId,
        string $productId,
        float $quantity,
        DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->stockTransferId = $stockTransferId;
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->createdAt = $createdAt;
    }

    public static function create(
        string $stockTransferId,
        string $productId,
        float $quantity
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

    public function updateQuantity(float $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('La quantité doit être supérieure à zéro');
        }

        $this->quantity = $quantity;
    }

    public function getId(): string { return $this->id; }
    public function getStockTransferId(): string { return $this->stockTransferId; }
    public function getProductId(): string { return $this->productId; }
    public function getQuantity(): float { return $this->quantity; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
}
