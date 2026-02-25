<?php

namespace Src\Domain\Pharmacy\Entities;

use Src\Domain\Pharmacy\ValueObjects\ProductCode;
use Src\Domain\Pharmacy\ValueObjects\MedicineType;
use Src\Domain\Pharmacy\ValueObjects\Dosage;
use Src\Domain\Pharmacy\ValueObjects\TypeUnite;
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
    private TypeUnite $typeUnite;
    private int $quantiteParUnite;
    private bool $estDivisible;
    private string $categoryId;
    private bool $isActive;
    private bool $requiresPrescription;
    private Quantity $minimumStock;
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
        TypeUnite $typeUnite,
        int $quantiteParUnite,
        bool $estDivisible,
        string $categoryId,
        bool $requiresPrescription = false,
        ?Quantity $minimumStock = null
    ) {
        if ($quantiteParUnite < 1) {
            throw new \InvalidArgumentException('quantite_par_unite must be at least 1');
        }
        $this->id = $id;
        $this->shopId = $shopId;
        $this->code = $code;
        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
        $this->dosage = $dosage;
        $this->price = $price;
        $this->stock = $stock;
        $this->typeUnite = $typeUnite;
        $this->quantiteParUnite = $quantiteParUnite;
        $this->estDivisible = $estDivisible;
        $this->categoryId = $categoryId;
        $this->requiresPrescription = $requiresPrescription;
        $this->minimumStock = $minimumStock ?? new Quantity(0);
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
    public function getMinimumStock(): Quantity { return $this->minimumStock; }
    public function getTypeUnite(): TypeUnite { return $this->typeUnite; }
    public function getQuantiteParUnite(): int { return $this->quantiteParUnite; }
    public function estDivisible(): bool { return $this->estDivisible; }
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

    /**
     * Décrémente le stock en respectant les règles métier :
     * - Si estDivisible = false, la quantité doit être un entier (pas de fraction).
     * - Sinon, les décimales sont autorisées (ex. 0.5 plaquette).
     */
    public function decreaseStock(Quantity $quantity): void
    {
        if (!$this->estDivisible) {
            $v = $quantity->getValue();
            if (abs($v - (int) $v) > 0.0001) {
                throw new \InvalidArgumentException(
                    'Ce produit n\'est pas vendu en fraction. La quantité doit être un nombre entier.'
                );
            }
        }
        if ($quantity->getValue() <= 0) {
            throw new \InvalidArgumentException('La quantité à retirer doit être strictement positive.');
        }
        if ($this->stock->getValue() < $quantity->getValue()) {
            throw new \InvalidArgumentException('Stock insuffisant.');
        }
        $this->removeStock($quantity);
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

    public function updateTypeUnite(TypeUnite $typeUnite): void
    {
        $this->typeUnite = $typeUnite;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateQuantiteParUnite(int $quantiteParUnite): void
    {
        if ($quantiteParUnite < 1) {
            throw new \InvalidArgumentException('quantite_par_unite must be at least 1');
        }
        $this->quantiteParUnite = $quantiteParUnite;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setEstDivisible(bool $estDivisible): void
    {
        $this->estDivisible = $estDivisible;
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
        TypeUnite $typeUnite,
        int $quantiteParUnite,
        bool $estDivisible,
        string $categoryId,
        bool $requiresPrescription = false,
        ?Quantity $minimumStock = null
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
            $typeUnite,
            $quantiteParUnite,
            $estDivisible,
            $categoryId,
            $requiresPrescription,
            $minimumStock
        );
    }
}