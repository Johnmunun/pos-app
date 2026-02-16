<?php

declare(strict_types=1);

namespace Src\Domain\Pharmacy\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * Entité Domain : Inventory
 *
 * Représente un inventaire physique du stock.
 * Permet de comparer le stock système avec le stock réel compté.
 */
class Inventory
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_CANCELLED = 'cancelled';

    private string $id;
    private string $shopId;
    private string $reference;
    private string $status;
    private ?DateTimeImmutable $startedAt;
    private ?DateTimeImmutable $validatedAt;
    private int $createdBy;
    private ?int $validatedBy;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    /** @var InventoryItem[] */
    private array $items = [];

    public function __construct(
        string $id,
        string $shopId,
        string $reference,
        string $status,
        ?DateTimeImmutable $startedAt,
        ?DateTimeImmutable $validatedAt,
        int $createdBy,
        ?int $validatedBy,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->id = $id;
        $this->shopId = $shopId;
        $this->reference = $reference;
        $this->status = $status;
        $this->startedAt = $startedAt;
        $this->validatedAt = $validatedAt;
        $this->createdBy = $createdBy;
        $this->validatedBy = $validatedBy;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * Crée un nouvel inventaire en brouillon
     */
    public static function create(string $shopId, int $createdBy): self
    {
        $now = new DateTimeImmutable();
        $reference = self::generateReference($now);

        return new self(
            Uuid::uuid4()->toString(),
            $shopId,
            $reference,
            self::STATUS_DRAFT,
            null,
            null,
            $createdBy,
            null,
            $now,
            $now
        );
    }

    /**
     * Génère une référence unique pour l'inventaire
     */
    private static function generateReference(DateTimeImmutable $date): string
    {
        return 'INV-' . $date->format('Ymd') . '-' . strtoupper(substr(Uuid::uuid4()->toString(), 0, 6));
    }

    /**
     * Démarre l'inventaire
     */
    public function start(): void
    {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new \DomainException('Seul un inventaire en brouillon peut être démarré.');
        }

        $this->status = self::STATUS_IN_PROGRESS;
        $this->startedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Valide l'inventaire
     */
    public function validate(int $validatedBy): void
    {
        if ($this->status !== self::STATUS_IN_PROGRESS) {
            throw new \DomainException('Seul un inventaire en cours peut être validé.');
        }

        $this->status = self::STATUS_VALIDATED;
        $this->validatedAt = new DateTimeImmutable();
        $this->validatedBy = $validatedBy;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Annule l'inventaire
     */
    public function cancel(): void
    {
        if ($this->status === self::STATUS_VALIDATED) {
            throw new \DomainException('Un inventaire validé ne peut pas être annulé.');
        }

        $this->status = self::STATUS_CANCELLED;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Vérifie si l'inventaire peut être modifié
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_IN_PROGRESS], true);
    }

    /**
     * Ajoute ou met à jour les items
     * 
     * @param InventoryItem[] $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    /**
     * Calcule le total des écarts positifs
     */
    public function getTotalPositiveDifference(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $diff = $item->getDifference();
            if ($diff > 0) {
                $total += $diff;
            }
        }
        return $total;
    }

    /**
     * Calcule le total des écarts négatifs
     */
    public function getTotalNegativeDifference(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $diff = $item->getDifference();
            if ($diff < 0) {
                $total += abs($diff);
            }
        }
        return $total;
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getShopId(): string { return $this->shopId; }
    public function getReference(): string { return $this->reference; }
    public function getStatus(): string { return $this->status; }
    public function getStartedAt(): ?DateTimeImmutable { return $this->startedAt; }
    public function getValidatedAt(): ?DateTimeImmutable { return $this->validatedAt; }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getValidatedBy(): ?int { return $this->validatedBy; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTimeImmutable { return $this->updatedAt; }

    /** @return InventoryItem[] */
    public function getItems(): array { return $this->items; }
}
