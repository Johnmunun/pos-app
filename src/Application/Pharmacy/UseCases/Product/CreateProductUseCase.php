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

        // Create value objects
        $productCode = new ProductCode($dto->productCode);
        $price = new Money($dto->price, $dto->currency);
        $cost = $dto->cost ? new Money($dto->cost, $dto->currency) : null;
        $minimumStock = new Quantity($dto->minimumStock);
        
        $medicineType = $dto->medicineType ? new MedicineType($dto->medicineType) : null;
        $dosage = $dto->dosage ? new Dosage($dto->dosage) : null;

        // Create product entity
        $product = Product::create(
            $dto->shopId,
            $dto->name,
            $productCode,
            $dto->description,
            $dto->categoryId,
            $price,
            $cost,
            $minimumStock,
            $dto->unit,
            $medicineType,
            $dosage,
            $dto->prescriptionRequired,
            $dto->manufacturer,
            $dto->supplierId
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