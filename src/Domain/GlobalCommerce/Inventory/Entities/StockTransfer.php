<?php

namespace Src\Domain\GlobalCommerce\Inventory\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * Entité Domain : StockTransfer (GlobalCommerce)
 *
 * Représente un transfert de stock entre deux magasins (shops) d'un même tenant.
 * Le transfert suit un workflow : draft → validated ou cancelled
 */
class StockTransfer
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_CANCELLED = 'cancelled';

    private string $id;
    private string $tenantId;
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
        string $tenantId,
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
        $this->tenantId = $tenantId;
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

    public static function create(
        string $tenantId,
        string $fromShopId,
        string $toShopId,
        int $createdBy,
        ?string $notes = null
    ): self {
        if ($fromShopId === $toShopId) {
            throw new \InvalidArgumentException('Le magasin source et destination doivent être différents');
        }

        $reference = 'GCT-' . date('Ymd') . '-' . strtoupper(substr(Uuid::uuid4()->toString(), 0, 6));

        return new self(
            Uuid::uuid4()->toString(),
            $tenantId,
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

    public function cancel(): void
    {
        if ($this->status === self::STATUS_VALIDATED) {
            throw new \InvalidArgumentException('Un transfert validé ne peut pas être annulé');
        }

        $this->status = self::STATUS_CANCELLED;
    }

    public function canBeEdited(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function addItem(StockTransferItem $item): void
    {
        if (!$this->canBeEdited()) {
            throw new \InvalidArgumentException('Ce transfert ne peut plus être modifié');
        }

        $this->items[] = $item;
    }

    /** @param StockTransferItem[] $items */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function getTotalItems(): int
    {
        return count($this->items);
    }

    public function getTotalQuantity(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item->getQuantity();
        }
        return $total;
    }

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getReference(): string { return $this->reference; }
    public function getFromShopId(): string { return $this->fromShopId; }
    public function getToShopId(): string { return $this->toShopId; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getValidatedBy(): ?int { return $this->validatedBy; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getValidatedAt(): ?DateTimeImmutable { return $this->validatedAt; }
    public function getNotes(): ?string { return $this->notes; }

    /** @return StockTransferItem[] */
    public function getItems(): array
    {
        return $this->items;
    }
}
