<?php

namespace Src\Application\Pharmacy\UseCases\Product;

use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\CategoryRepositoryInterface;
use Src\Application\Pharmacy\DTO\CreateProductDTO;
use Src\Domain\Pharmacy\Entities\Product;
use Src\Domain\Pharmacy\ValueObjects\ProductCode;
use Src\Domain\Pharmacy\ValueObjects\MedicineType;
use Src\Domain\Pharmacy\ValueObjects\Dosage;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;
use DateTimeImmutable;

class CreateProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private CategoryRepositoryInterface $categoryRepository
    ) {}

    public function execute(CreateProductDTO $dto): Product
    {
        // Validate business rules
        $this->validateProductCode($dto->productCode);
        $this->validateCategory($dto->categoryId, $dto->shopId);
        $this->validateDosageForMedicine($dto);

        // Création des ValueObjects pour le Domain
        $productCode = new ProductCode($dto->productCode);
        $price = new Money($dto->price, $dto->currency);

        // Le Domain Product ne connaît pas le coût, l'unité, le stock minimum, le fabricant, le supplier, etc.
        // Ces informations sont gérées en Infrastructure (ProductModel) juste après l'appel au UseCase.

        // Le Domain exige un MedicineType non-null
        $medicineType = $dto->medicineType
            ? new MedicineType($dto->medicineType)
            : new MedicineType(MedicineType::getAllTypes()[0]);

        $dosage = $dto->dosage ? new Dosage($dto->dosage) : null;

        // Stock initial dans le Domain : 0 (les mouvements de stock sont gérés par les UseCases d'inventaire)
        $initialStock = new Quantity(0);

        // Création de l'entité Domain en respectant strictement la signature
        $product = Product::create(
            $dto->shopId,               // string $shopId
            $productCode,               // ProductCode $code
            $dto->name,                 // string $name
            $dto->description ?? '',    // string $description
            $medicineType,              // MedicineType $type
            $dosage,                    // ?Dosage $dosage
            $price,                     // Money $price
            $initialStock,              // Quantity $initialStock
            $dto->categoryId,           // string $categoryId
            $dto->prescriptionRequired  // bool $requiresPrescription
        );

        // Save product
        $this->productRepository->save($product);

        return $product;
    }

    private function validateProductCode(string $productCode): void
    {
        if ($this->productRepository->existsByCode($productCode)) {
            throw new \InvalidArgumentException("Product code {$productCode} already exists");
        }
    }

    private function validateCategory(string $categoryId, string $shopId): void
    {
        $category = $this->categoryRepository->findById($categoryId);
        if (!$category || $category->getShopId() !== $shopId) {
            throw new \InvalidArgumentException('Invalid category');
        }
    }

    private function validateDosageForMedicine(CreateProductDTO $dto): void
    {
        if ($dto->medicineType && !$dto->dosage) {
            throw new \InvalidArgumentException('Dosage is required for medicine products');
        }
        
        if (!$dto->medicineType && $dto->dosage) {
            throw new \InvalidArgumentException('Dosage can only be set for medicine products');
        }
    }
}