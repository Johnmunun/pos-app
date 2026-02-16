<?php

namespace Src\Application\Pharmacy\UseCases\Purchases;

use Illuminate\Support\Facades\DB;
use Src\Application\Pharmacy\DTO\CreateBatchDTO;
use Src\Application\Pharmacy\DTO\ReceiveLineDTO;
use Src\Application\Pharmacy\DTO\ReceivePurchaseOrderDTO;
use Src\Application\Pharmacy\DTO\UpdateStockDTO;
use Src\Application\Pharmacy\UseCases\Batch\AddBatchUseCase;
use Src\Domain\Pharmacy\Entities\PurchaseOrder;
use Src\Domain\Pharmacy\Repositories\PurchaseOrderLineRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\PurchaseOrderRepositoryInterface;
use Src\Application\Pharmacy\UseCases\Inventory\UpdateStockUseCase;
use Src\Shared\ValueObjects\Quantity;

/**
 * Use case : réception de marchandises pour un PO avec création de lots.
 *
 * Supporte la réception complète ou partielle avec informations de lot obligatoires.
 */
class ReceivePurchaseOrderUseCase
{
    public function __construct(
        private PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private PurchaseOrderLineRepositoryInterface $purchaseOrderLineRepository,
        private UpdateStockUseCase $updateStockUseCase,
        private AddBatchUseCase $addBatchUseCase
    ) {}

    /**
     * Execute with batch information.
     * 
     * @param ReceivePurchaseOrderDTO $dto DTO containing batch info per line
     */
    public function executeWithBatches(ReceivePurchaseOrderDTO $dto): void
    {
        DB::transaction(function () use ($dto): void {
            $po = $this->purchaseOrderRepository->findById($dto->purchaseOrderId);

            if (!$po) {
                throw new \InvalidArgumentException('Bon de commande non trouvé.');
            }

            if (!in_array($po->getStatus(), [PurchaseOrder::STATUS_CONFIRMED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED], true)) {
                throw new \LogicException('Le bon de commande doit être confirmé avant réception.');
            }

            $lines = $this->purchaseOrderLineRepository->findByPurchaseOrder($dto->purchaseOrderId);
            if (count($lines) === 0) {
                throw new \LogicException('Le bon de commande n\'a pas de lignes.');
            }

            $shopId = $po->getShopId();

            // Index lines by ID for quick lookup
            $linesById = [];
            foreach ($lines as $line) {
                $linesById[$line->getId()] = $line;
            }

            // Index batch info by line ID
            $batchInfoByLineId = [];
            foreach ($dto->lines as $lineDto) {
                $batchInfoByLineId[$lineDto->lineId] = $lineDto;
            }

            $allFullyReceived = true;

            foreach ($lines as $line) {
                $remaining = $line->getOrderedQuantity()->getValue() - $line->getReceivedQuantity()->getValue();
                if ($remaining <= 0) {
                    continue;
                }

                // Get batch info for this line
                $batchInfo = $batchInfoByLineId[$line->getId()] ?? null;
                
                if (!$batchInfo) {
                    throw new \InvalidArgumentException(
                        sprintf('Informations de lot manquantes pour le produit de la ligne %s.', $line->getId())
                    );
                }

                // Calculate quantity to receive
                $quantityToReceive = $batchInfo->quantity ?? $remaining;
                if ($quantityToReceive > $remaining) {
                    $quantityToReceive = $remaining;
                }

                // Register reception
                $line->registerReception(new Quantity($quantityToReceive));
                $this->purchaseOrderLineRepository->save($line);

                // Create batch with expiration date
                $batchDto = new CreateBatchDTO(
                    shopId: $shopId,
                    productId: $line->getProductId(),
                    batchNumber: $batchInfo->batchNumber,
                    quantity: $quantityToReceive,
                    expirationDate: $batchInfo->expirationDate,
                    purchaseOrderId: $po->getId(),
                    purchaseOrderLineId: $line->getId()
                );

                $this->addBatchUseCase->execute($batchDto);

                // Also update main product stock for backward compatibility
                $stockDto = new UpdateStockDTO(
                    $shopId,
                    $line->getProductId(),
                    $quantityToReceive,
                    $batchInfo->batchNumber,
                    $batchInfo->expirationDate->format('Y-m-d'),
                    $po->getSupplierId(),
                    $po->getId(),
                    $dto->userId
                );

                $this->updateStockUseCase->addStock($stockDto);

                if (!$line->isFullyReceived()) {
                    $allFullyReceived = false;
                }
            }

            if ($allFullyReceived) {
                $po->markReceived();
            } else {
                $po->markPartiallyReceived();
            }

            $this->purchaseOrderRepository->save($po);
        });
    }

    /**
     * Execute simple reception without batch info (backward compatible).
     * 
     * @deprecated Use executeWithBatches() instead
     */
    public function execute(string $purchaseOrderId, int $userId): void
    {
        DB::transaction(function () use ($purchaseOrderId, $userId): void {
            $po = $this->purchaseOrderRepository->findById($purchaseOrderId);

            if (!$po) {
                throw new \InvalidArgumentException('Purchase order not found');
            }

            if (!in_array($po->getStatus(), [PurchaseOrder::STATUS_CONFIRMED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED], true)) {
                throw new \LogicException('Purchase order must be confirmed before reception');
            }

            $lines = $this->purchaseOrderLineRepository->findByPurchaseOrder($purchaseOrderId);
            if (count($lines) === 0) {
                throw new \LogicException('Purchase order has no lines');
            }

            $shopId = $po->getShopId();

            $allFullyReceived = true;

            foreach ($lines as $line) {
                $remaining = $line->getOrderedQuantity()->getValue() - $line->getReceivedQuantity()->getValue();
                if ($remaining <= 0) {
                    continue;
                }

                // Mettre à jour la quantité reçue côté Domain
                $line->registerReception(new Quantity($remaining));
                $this->purchaseOrderLineRepository->save($line);

                // Créer le DTO de stock pour cette ligne
                $dto = new UpdateStockDTO(
                    $shopId,
                    $line->getProductId(),
                    $remaining,
                    null,               // batch_number
                    null,               // expiry_date
                    $po->getSupplierId(),
                    $po->getId(),
                    $userId
                );

                $this->updateStockUseCase->addStock($dto);

                if (!$line->isFullyReceived()) {
                    $allFullyReceived = false;
                }
            }

            if ($allFullyReceived) {
                $po->markReceived();
            } else {
                $po->markPartiallyReceived();
            }

            $this->purchaseOrderRepository->save($po);
        });
    }
}

