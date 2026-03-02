<?php

namespace Src\Infrastructure\Pharmacy\Adapters;

use Src\Application\Pharmacy\DTO\CreateBatchDTO;
use Src\Application\Pharmacy\UseCases\Batch\AddBatchUseCaseInterface;
use Src\Domain\Pharmacy\Entities\ProductBatch;

/**
 * Stub AddBatchUseCase pour Hardware qui ne crée pas de batches
 * (Hardware n'utilise pas de système de lots)
 * 
 * Cette classe implémente la même interface que AddBatchUseCase
 * mais ne sauvegarde pas les batches en base.
 */
class NoOpAddBatchUseCase implements AddBatchUseCaseInterface
{
    public function execute(CreateBatchDTO $dto): ProductBatch
    {
        // Pour Hardware, on ne crée pas de batches
        // On retourne un batch minimal pour satisfaire l'interface
        // mais il ne sera pas sauvegardé en base
        // expirationDate est toujours défini dans CreateBatchDTO, donc on l'utilise directement
        $expirationDate = new \Src\Domain\Pharmacy\ValueObjects\ExpirationDate($dto->expirationDate);
            
        return ProductBatch::create(
            id: \Ramsey\Uuid\Uuid::uuid4()->toString(),
            shopId: $dto->shopId,
            productId: $dto->productId,
            batchNumber: new \Src\Domain\Pharmacy\ValueObjects\BatchNumber($dto->batchNumber ?? 'NO-BATCH'),
            quantity: new \Src\Shared\ValueObjects\Quantity($dto->quantity),
            expirationDate: $expirationDate,
            purchaseOrderId: $dto->purchaseOrderId,
            purchaseOrderLineId: $dto->purchaseOrderLineId
        );
        // Note: Ce batch n'est pas sauvegardé, c'est juste pour satisfaire l'interface
    }
}
