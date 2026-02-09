<?php

namespace Src\Application\Pharmacy\UseCases\Inventory;

use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\BatchRepositoryInterface;
use Src\Application\Pharmacy\DTO\UpdateStockDTO;
use Src\Domain\Pharmacy\Entities\Batch;
use Src\Domain\Pharmacy\ValueObjects\ExpiryDate;
use Src\Shared\ValueObjects\Quantity;
use DateTimeImmutable;

class UpdateStockUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private BatchRepositoryInterface $batchRepository
    ) {}

    public function addStock(UpdateStockDTO $dto): Batch
    {
        // Validate product exists
        $product = $this->productRepository->findById($dto->productId);
        if (!$product) {
            throw new \InvalidArgumentException("Product not found");
        }

        // Validate batch number uniqueness
        if ($dto->batchNumber && $this->batchRepository->findByBatchNumber($dto->batchNumber, $dto->shopId)) {
            throw new \InvalidArgumentException("Batch number already exists");
        }

        // Create expiry date
        $expiryDate = $dto->expiryDate ? new ExpiryDate(new DateTimeImmutable($dto->expiryDate)) : null;

        // Create new batch
        $batch = Batch::create(
            $dto->shopId,
            $dto->productId,
            $dto->batchNumber,
            $expiryDate,
            new Quantity($dto->quantity),
            $dto->supplierId,
            $dto->purchaseOrderId
        );

        // Save batch
        $this->batchRepository->save($batch);

        // Update product stock
        $product->addStock(new Quantity($dto->quantity));
        $this->productRepository->update($product);

        return $batch;
    }

    public function removeStock(string $productId, int $quantity, string $shopId): void
    {
        // Validate product exists
        $product = $this->productRepository->findById($productId);
        if (!$product) {
            throw new \InvalidArgumentException("Product not found");
        }

        // Validate sufficient stock
        if ($product->getStock()->getValue() < $quantity) {
            throw new \InvalidArgumentException("Insufficient stock available");
        }

        // Get available batches (FIFO approach)
        $batches = $this->batchRepository->findByProduct($productId);
        $remainingQuantity = $quantity;

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $batchQuantity = $batch->getQuantity()->getValue();
            $consumeQuantity = min($batchQuantity, $remainingQuantity);

            if ($consumeQuantity > 0) {
                $batch->consume(new Quantity($consumeQuantity));
                $this->batchRepository->update($batch);
                $remainingQuantity -= $consumeQuantity;
            }
        }

        // Update product stock
        $product->removeStock(new Quantity($quantity));
        $this->productRepository->update($product);

        if ($remainingQuantity > 0) {
            throw new \LogicException("Could not consume all requested quantity");
        }
    }

    public function adjustStock(string $productId, int $newQuantity, string $shopId): void
    {
        // Validate product exists
        $product = $this->productRepository->findById($productId);
        if (!$product) {
            throw new \InvalidArgumentException("Product not found");
        }

        $currentStock = $product->getStock()->getValue();
        $difference = $newQuantity - $currentStock;

        if ($difference > 0) {
            // Add stock
            $dto = new UpdateStockDTO(
                $shopId,
                $productId,
                $difference,
                null,
                null,
                null,
                null
            );
            $this->addStock($dto);
        } elseif ($difference < 0) {
            // Remove stock
            $this->removeStock($productId, abs($difference), $shopId);
        }
        // If difference = 0, no action needed
    }
}