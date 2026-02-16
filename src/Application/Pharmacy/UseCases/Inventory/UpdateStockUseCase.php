<?php

namespace Src\Application\Pharmacy\UseCases\Inventory;

use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\BatchRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\StockMovementRepositoryInterface;
use Src\Application\Pharmacy\DTO\UpdateStockDTO;
use Src\Domain\Pharmacy\Entities\Batch;
use Src\Domain\Pharmacy\Entities\StockMovement;
use Src\Domain\Pharmacy\ValueObjects\ExpiryDate;
use Src\Shared\ValueObjects\Quantity;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

class UpdateStockUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private BatchRepositoryInterface $batchRepository,
        private StockMovementRepositoryInterface $stockMovementRepository
    ) {}

    public function addStock(UpdateStockDTO $dto): Batch
    {
        return DB::transaction(function () use ($dto): Batch {
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
            $quantity = new Quantity($dto->quantity);
            $product->addStock($quantity);
            $this->productRepository->update($product);

            // Enregistrer le mouvement de stock (IN)
            $reference = $dto->purchaseOrderId ?? $dto->batchNumber ?? 'STOCK_IN';
            $movement = StockMovement::in(
                $dto->shopId,
                $dto->productId,
                $quantity,
                $reference,
                $dto->createdBy ?? 0
            );
            $this->stockMovementRepository->save($movement);
            
            return $batch;
        });
    }

    public function removeStock(string $productId, int $quantity, string $shopId, int $createdBy = 0, ?string $reference = null): void
    {
        $ref = $reference ?? 'STOCK_OUT';
        DB::transaction(function () use ($productId, $quantity, $shopId, $createdBy, $ref): void {
            // Validate product exists
            $product = $this->productRepository->findById($productId);
            if (!$product) {
                throw new \InvalidArgumentException("Product not found");
            }
            
            // Validate sufficient stock (interdiction stock négatif)
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
            $qtyVo = new Quantity($quantity);
            $product->removeStock($qtyVo);
            $this->productRepository->update($product);
            
            if ($remainingQuantity > 0) {
                throw new \LogicException("Could not consume all requested quantity");
            }

            // Enregistrer le mouvement de stock (OUT)
            $movement = StockMovement::out(
                $shopId,
                $productId,
                $qtyVo,
                $ref,
                $createdBy
            );
            $this->stockMovementRepository->save($movement);
        });
    }

    public function adjustStock(string $productId, int $newQuantity, string $shopId, int $createdBy = 0): void
    {
        DB::transaction(function () use ($productId, $newQuantity, $shopId, $createdBy): void {
            // Validate product exists
            $product = $this->productRepository->findById($productId);
            if (!$product) {
                throw new \InvalidArgumentException("Product not found");
            }
            
            $currentStock = $product->getStock()->getValue();
            $difference = $newQuantity - $currentStock;
            
            if ($difference === 0) {
                return;
            }

            if ($difference > 0) {
                // Add stock
                $dto = new UpdateStockDTO(
                    $shopId,
                    $productId,
                    $difference,
                    null,
                    null,
                    null,
                    null,
                    $createdBy
                );
                $this->addStock($dto);
            } else {
                // Remove stock (vérifie déjà le stock suffisant)
                $this->removeStock($productId, abs($difference), $shopId, $createdBy);
            }

            // Mouvement d'ajustement pour tracer l'opération globale
            $movement = StockMovement::adjustment(
                $shopId,
                $productId,
                new Quantity($newQuantity),
                'STOCK_ADJUST',
                $createdBy
            );
            $this->stockMovementRepository->save($movement);
        });
    }
}