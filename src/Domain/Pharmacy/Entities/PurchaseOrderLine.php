<?php

namespace Src\Domain\Pharmacy\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;

/**
 * Entité de domaine : PurchaseOrderLine
 *
 * Ligne d'un bon de commande fournisseur.
 */
class PurchaseOrderLine
{
    private string $id;
    private string $purchaseOrderId;
    private string $productId;
    private Quantity $orderedQuantity;
    private Quantity $receivedQuantity;
    private Money $unitCost;
    private Money $lineTotal;
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $purchaseOrderId,
        string $productId,
        Quantity $orderedQuantity,
        Quantity $receivedQuantity,
        Money $unitCost,
        Money $lineTotal,
        DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->purchaseOrderId = $purchaseOrderId;
        $this->productId = $productId;
        $this->orderedQuantity = $orderedQuantity;
        $this->receivedQuantity = $receivedQuantity;
        $this->unitCost = $unitCost;
        $this->lineTotal = $lineTotal;
        $this->createdAt = $createdAt;
    }

    public static function create(
        string $purchaseOrderId,
        string $productId,
        Quantity $orderedQuantity,
        Money $unitCost
    ): self {
        $lineTotal = $unitCost->multiply($orderedQuantity->getValue());

        return new self(
            Uuid::uuid4()->toString(),
            $purchaseOrderId,
            $productId,
            $orderedQuantity,
            new Quantity(0),
            $unitCost,
            $lineTotal,
            new DateTimeImmutable()
        );
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getPurchaseOrderId(): string { return $this->purchaseOrderId; }
    public function getProductId(): string { return $this->productId; }
    public function getOrderedQuantity(): Quantity { return $this->orderedQuantity; }
    public function getReceivedQuantity(): Quantity { return $this->receivedQuantity; }
    public function getUnitCost(): Money { return $this->unitCost; }
    public function getLineTotal(): Money { return $this->lineTotal; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }

    public function registerReception(Quantity $quantity): void
    {
        // On n'autorise pas de réception au-delà de la quantité commandée
        $newReceived = $this->receivedQuantity->add($quantity);
        if ($newReceived->getValue() > $this->orderedQuantity->getValue()) {
            throw new \InvalidArgumentException('Received quantity cannot exceed ordered quantity');
        }

        $this->receivedQuantity = $newReceived;
    }

    public function isFullyReceived(): bool
    {
        return $this->receivedQuantity->getValue() >= $this->orderedQuantity->getValue();
    }
}

