<?php

namespace Src\Domain\Pharmacy\Entities;

use Src\Domain\Pharmacy\ValueObjects\ProductCode;
use Src\Domain\Pharmacy\ValueObjects\MedicineType;
use Src\Domain\Pharmacy\ValueObjects\Dosage;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

class Product
{
    private string $id;
    private string $shopId;
    private ProductCode $code;
    private string $name;
    private string $description;
    private MedicineType $type;
    private ?Dosage $dosage;
    private Money $price;
    private Quantity $stock;
    private string $categoryId;
    private bool $isActive;
    private bool $requiresPrescription;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $shopId,
        ProductCode $code,
        string $name,
        string $description,
        MedicineType $type,
        ?Dosage $dosage,
        Money $price,
        Quantity $stock,
        string $categoryId,
        bool $requiresPrescription = false
    ) {
        $this->id = $id;
        $this->shopId = $shopId;
        $this->code = $code;
        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
        $this->dosage = $dosage;
        $this->price = $price;
        $this->stock = $stock;
        $this->categoryId = $categoryId;
        $this->requiresPrescription = $requiresPrescription;
        $this->isActive = true;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getShopId(): string { return $this->shopId; }
    public function getCode(): ProductCode { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getType(): MedicineType { return $this->type; }
    public function getDosage(): ?Dosage { return $this->dosage; }
    public function getPrice(): Money { return $this->price; }
    public function getStock(): Quantity { return $this->stock; }
    public function getCategoryId(): string { return $this->categoryId; }
    public function isActive(): bool { return $this->isActive; }
    public function requiresPrescription(): bool { return $this->requiresPrescription; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    // Business methods
    public function updateStock(Quantity $newStock): void
    {
        $this->stock = $newStock;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function addStock(Quantity $quantity): void
    {
        $this->stock = $this->stock->add($quantity);
        $this->updatedAt = new DateTimeImmutable();
    }

    public function removeStock(Quantity $quantity): void
    {
        $this->stock = $this->stock->subtract($quantity);
        $this->updatedAt = new DateTimeImmutable();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updatePrice(Money $newPrice): void
    {
        $this->price = $newPrice;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateCode(ProductCode $code): void
    {
        $this->code = $code;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateDescription(string $description): void
    {
        $this->description = $description;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateCategory(string $categoryId): void
    {
        $this->categoryId = $categoryId;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateMedicineType(?MedicineType $type): void
    {
        if ($type !== null) {
            $this->type = $type;
            $this->updatedAt = new DateTimeImmutable();
        }
    }

    public function updateDosage(?Dosage $dosage): void
    {
        $this->dosage = $dosage;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setRequiresPrescription(bool $requires): void
    {
        $this->requiresPrescription = $requires;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isLowStock(int $threshold = 10): bool
    {
        return $this->stock->getValue() <= $threshold;
    }

    public function isInStock(): bool
    {
        return !$this->stock->isZero();
    }

    // Static factory method
    public static function create(
        string $shopId,
        ProductCode $code,
        string $name,
        string $description,
        MedicineType $type,
        ?Dosage $dosage,
        Money $price,
        Quantity $initialStock,
        string $categoryId,
        bool $requiresPrescription = false
    ): self {
        return new self(
            Uuid::uuid4()->toString(),
            $shopId,
            $code,
            $name,
            $description,
            $type,
            $dosage,
            $price,
            $initialStock,
            $categoryId,
            $requiresPrescription
        );
    }
}