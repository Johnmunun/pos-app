<?php

namespace Src\Domain\GlobalCommerce\Inventory\Entities;

use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * Produit générique GlobalCommerce (tous secteurs).
 * Ne contient AUCUNE logique spécifique Pharmacy/Hardware.
 */
class Product
{
    private string $id;
    private string $shopId;
    private string $sku;
    private ?string $barcode;
    private string $name;
    private string $description;
    private string $categoryId;
    private Money $purchasePrice;
    private Money $salePrice;
    private Quantity $stock;
    private Quantity $minimumStock;
    private bool $isWeighted;
    private bool $hasExpiration;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $shopId,
        string $sku,
        ?string $barcode,
        string $name,
        string $description,
        string $categoryId,
        Money $purchasePrice,
        Money $salePrice,
        Quantity $stock,
        Quantity $minimumStock,
        bool $isWeighted,
        bool $hasExpiration,
        bool $isActive = true
    ) {
        $this->id = $id;
        $this->shopId = $shopId;
        $this->sku = $sku;
        $this->barcode = $barcode;
        $this->name = $name;
        $this->description = $description;
        $this->categoryId = $categoryId;
        $this->purchasePrice = $purchasePrice;
        $this->salePrice = $salePrice;
        $this->stock = $stock;
        $this->minimumStock = $minimumStock;
        $this->isWeighted = $isWeighted;
        $this->hasExpiration = $hasExpiration;
        $this->isActive = $isActive;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getShopId(): string { return $this->shopId; }
    public function getSku(): string { return $this->sku; }
    public function getBarcode(): ?string { return $this->barcode; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getCategoryId(): string { return $this->categoryId; }
    public function getPurchasePrice(): Money { return $this->purchasePrice; }
    public function getSalePrice(): Money { return $this->salePrice; }
    public function getStock(): Quantity { return $this->stock; }
    public function getMinimumStock(): Quantity { return $this->minimumStock; }
    public function isWeighted(): bool { return $this->isWeighted; }
    public function hasExpiration(): bool { return $this->hasExpiration; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    // Métier stock
    public function addStock(Quantity $quantity): void
    {
        $this->stock = $this->stock->add($quantity);
        $this->touch();
    }

    public function removeStock(Quantity $quantity): void
    {
        if ($quantity->getValue() <= 0) {
            throw new \InvalidArgumentException('La quantité doit être strictement positive.');
        }
        if ($this->stock->getValue() < $quantity->getValue()) {
            throw new \InvalidArgumentException('Stock insuffisant.');
        }
        $this->stock = $this->stock->subtract($quantity);
        $this->touch();
    }

    public function updatePrices(Money $purchasePrice, Money $salePrice): void
    {
        $this->purchasePrice = $purchasePrice;
        $this->salePrice = $salePrice;
        $this->touch();
    }

    public function markInactive(): void
    {
        $this->isActive = false;
        $this->touch();
    }

    public function markActive(): void
    {
        $this->isActive = true;
        $this->touch();
    }

    public function isLowStock(): bool
    {
        return $this->stock->getValue() <= $this->minimumStock->getValue();
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public static function create(
        string $shopId,
        string $sku,
        ?string $barcode,
        string $name,
        string $description,
        string $categoryId,
        Money $purchasePrice,
        Money $salePrice,
        Quantity $initialStock,
        Quantity $minimumStock,
        bool $isWeighted,
        bool $hasExpiration
    ): self {
        return new self(
            Uuid::uuid4()->toString(),
            $shopId,
            $sku,
            $barcode,
            $name,
            $description,
            $categoryId,
            $purchasePrice,
            $salePrice,
            $initialStock,
            $minimumStock,
            $isWeighted,
            $hasExpiration,
            true
        );
    }
}

