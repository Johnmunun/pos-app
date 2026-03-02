<?php

namespace Src\Infrastructure\Pharmacy\Adapters;

use Src\Application\Pharmacy\DTO\UpdateStockDTO;
use Src\Application\Pharmacy\UseCases\Inventory\UpdateStockUseCase;
use Src\Domain\Pharmacy\Entities\Batch;
use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\StockMovementRepositoryInterface;
use Src\Domain\Pharmacy\Entities\StockMovement;
use Src\Shared\ValueObjects\Quantity;
use Illuminate\Support\Facades\DB;

/**
 * UpdateStockUseCase adapté pour Hardware qui ne crée pas de batches
 * (Hardware n'utilise pas de système de lots)
 */
class HardwareUpdateStockUseCase extends UpdateStockUseCase
{
    public function __construct(
        ProductRepositoryInterface $productRepository,
        StockMovementRepositoryInterface $stockMovementRepository
    ) {
        // Ne pas appeler le parent car il nécessite BatchRepositoryInterface
        // On initialise directement les propriétés
        $this->productRepository = $productRepository;
        $this->stockMovementRepository = $stockMovementRepository;
    }
    
    private ProductRepositoryInterface $productRepository;
    private StockMovementRepositoryInterface $stockMovementRepository;

    public function addStock(UpdateStockDTO $dto): Batch
    {
        return DB::transaction(function () use ($dto): Batch {
            // Validate product exists
            $product = $this->productRepository->findById($dto->productId);
            if (!$product) {
                throw new \InvalidArgumentException("Product not found");
            }
            
            // Update product stock (sans créer de batch pour Hardware)
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
            
            // Retourner un batch minimal pour satisfaire l'interface (non sauvegardé)
            // Créer une date d'expiration fictive pour Hardware (non utilisée)
            $fakeExpiryDate = new \Src\Domain\Pharmacy\ValueObjects\ExpiryDate(
                new \DateTimeImmutable('+1 year')
            );
            return Batch::create(
                $dto->shopId,
                $dto->productId,
                $dto->batchNumber ?? 'NO-BATCH-HW',
                $fakeExpiryDate,
                $quantity,
                $dto->supplierId,
                $dto->purchaseOrderId
            );
            // Note: Ce batch n'est pas sauvegardé, c'est juste pour satisfaire l'interface
        });
    }

    public function removeStock(string $productId, float $quantity, string $shopId, int $createdBy = 0, ?string $reference = null): void
    {
        $ref = $reference ?? 'STOCK_OUT';
        $quantity = round($quantity, 4);
        DB::transaction(function () use ($productId, $quantity, $shopId, $createdBy, $ref): void {
            // Validate product exists
            $product = $this->productRepository->findById($productId);
            if (!$product) {
                throw new \InvalidArgumentException("Product not found");
            }

            // Validate sufficient stock
            if ($product->getStock()->getValue() < $quantity) {
                throw new \InvalidArgumentException("Insufficient stock available");
            }

            // Update product stock (sans gérer les batches pour Hardware)
            $qtyVo = new Quantity($quantity);
            $product->decreaseStock($qtyVo);
            $this->productRepository->update($product);

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

    public function adjustStock(string $productId, float $newQuantity, string $shopId, int $createdBy = 0): void
    {
        $newQuantity = round($newQuantity, 4);
        DB::transaction(function () use ($productId, $newQuantity, $shopId, $createdBy): void {
            // Validate product exists
            $product = $this->productRepository->findById($productId);
            if (!$product) {
                throw new \InvalidArgumentException("Product not found");
            }

            $currentStock = $product->getStock()->getValue();
            $difference = $newQuantity - $currentStock;
            
            if (abs($difference) < 0.0001) {
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
                // Remove stock
                $this->removeStock($productId, abs($difference), $shopId, $createdBy);
            }

            // Mouvement d'ajustement
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
