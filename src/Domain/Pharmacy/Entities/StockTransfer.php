<?php

namespace Src\Domain\Pharmacy\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * Entité Domain : StockTransfer
 *
 * Représente un transfert de stock entre deux magasins (shops) d'une même pharmacie.
 * Le transfert suit un workflow : draft → validated ou cancelled
 */
class StockTransfer
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_CANCELLED = 'cancelled';

    private string $id;
    private string $pharmacyId;
    private string $reference;
    private string $fromShopId;
    private string $toShopId;
    private string $status;
    private int $createdBy;
    private ?int $validatedBy;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $validatedAt;
    private ?string $notes;

    /** @var StockTransferItem[] */
    private array $items = [];

    public function __construct(
        string $id,
        string $pharmacyId,
        string $reference,
        string $fromShopId,
        string $toShopId,
        string $status,
        int $createdBy,
        ?int $validatedBy,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $validatedAt,
        ?string $notes = null
    ) {
        $this->id = $id;
        $this->pharmacyId = $pharmacyId;
        $this->reference = $reference;
        $this->fromShopId = $fromShopId;
        $this->toShopId = $toShopId;
        $this->status = $status;
        $this->createdBy = $createdBy;
        $this->validatedBy = $validatedBy;
        $this->createdAt = $createdAt;
        $this->validatedAt = $validatedAt;
        $this->notes = $notes;
    }

    /**
     * Crée un nouveau transfert en statut draft
     */
    public static function create(
        string $pharmacyId,
        string $fromShopId,
        string $toShopId,
        int $createdBy,
        ?string $notes = null
    ): self {
        if ($fromShopId === $toShopId) {
            throw new \InvalidArgumentException('Le magasin source et destination doivent être différents');
        }

        $reference = self::generateReference();

        return new self(
            Uuid::uuid4()->toString(),
            $pharmacyId,
            $reference,
            $fromShopId,
            $toShopId,
            self::STATUS_DRAFT,
            $createdBy,
            null,
            new DateTimeImmutable(),
            null,
            $notes
        );
    }

    /**
     * Génère une référence unique pour le transfert
     */
    private static function generateReference(): string
    {
        return 'TRF-' . date('Ymd') . '-' . strtoupper(substr(Uuid::uuid4()->toString(), 0, 6));
    }

    /**
     * Valide le transfert
     */
    public function validate(int $validatedBy): void
    {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new \InvalidArgumentException('Seul un transfert en brouillon peut être validé');
        }

        if (empty($this->items)) {
            throw new \InvalidArgumentException('Le transfert doit contenir au moins un produit');
        }

        $this->status = self::STATUS_VALIDATED;
        $this->validatedBy = $validatedBy;
        $this->validatedAt = new DateTimeImmutable();
    }

    /**
     * Annule le transfert
     */
    public function cancel(): void
    {
        if ($this->status === self::STATUS_VALIDATED) {
            throw new \InvalidArgumentException('Un transfert validé ne peut pas être annulé');
        }

        $this->status = self::STATUS_CANCELLED;
    }

    /**
     * Vérifie si le transfert peut être modifié
     */
    public function canBeEdited(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Ajoute un item au transfert
     */
    public function addItem(StockTransferItem $item): void
    {
        if (!$this->canBeEdited()) {
            throw new \InvalidArgumentException('Ce transfert ne peut plus être modifié');
        }

        $this->items[] = $item;
    }

    /**
     * Définit les items du transfert
     * 
     * @param StockTransferItem[] $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    /**
     * Calcule le nombre total d'items
     */
    public function getTotalItems(): int
    {
        return count($this->items);
    }

    /**
     * Calcule la quantité totale transférée
     */
    public function getTotalQuantity(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->getQuantity();
        }
        return $total;
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getPharmacyId(): string
    {
        return $this->pharmacyId;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getFromShopId(): string
    {
        return $this->fromShopId;
    }

    public function getToShopId(): string
    {
        return $this->toShopId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function getValidatedBy(): ?int
    {
        return $this->validatedBy;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getValidatedAt(): ?DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * @return StockTransferItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
