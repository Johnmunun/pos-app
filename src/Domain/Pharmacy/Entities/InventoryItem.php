<?php

declare(strict_types=1);

namespace Src\Domain\Pharmacy\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * Entité Domain : InventoryItem
 *
 * Représente une ligne d'inventaire pour un produit spécifique.
 * Contient la quantité système (snapshot), la quantité comptée et la différence.
 */
class InventoryItem
{
    private string $id;
    private string $inventoryId;
    private string $productId;
    private int $systemQuantity;
    private ?int $countedQuantity;
    private int $difference;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $inventoryId,
        string $productId,
        int $systemQuantity,
        ?int $countedQuantity,
        int $difference,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->id = $id;
        $this->inventoryId = $inventoryId;
        $this->productId = $productId;
        $this->systemQuantity = $systemQuantity;
        $this->countedQuantity = $countedQuantity;
        $this->difference = $difference;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * Crée un nouvel item d'inventaire avec snapshot du stock système
     */
    public static function create(
        string $inventoryId,
        string $productId,
        int $systemQuantity
    ): self {
        $now = new DateTimeImmutable();

        return new self(
            Uuid::uuid4()->toString(),
            $inventoryId,
            $productId,
            $systemQuantity,
            null, // Quantité comptée non saisie
            0,    // Différence à 0 par défaut
            $now,
            $now
        );
    }

    /**
     * Met à jour la quantité comptée et recalcule la différence
     */
    public function updateCountedQuantity(int $countedQuantity): void
    {
        $this->countedQuantity = $countedQuantity;
        $this->difference = $countedQuantity - $this->systemQuantity;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Vérifie si la quantité a été comptée
     */
    public function isCounted(): bool
    {
        return $this->countedQuantity !== null;
    }

    /**
     * Vérifie s'il y a un écart
     */
    public function hasDifference(): bool
    {
        return $this->difference !== 0;
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getInventoryId(): string { return $this->inventoryId; }
    public function getProductId(): string { return $this->productId; }
    public function getSystemQuantity(): int { return $this->systemQuantity; }
    public function getCountedQuantity(): ?int { return $this->countedQuantity; }
    public function getDifference(): int { return $this->difference; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTimeImmutable { return $this->updatedAt; }
}
