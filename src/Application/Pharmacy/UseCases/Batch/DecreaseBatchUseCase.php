<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\Batch;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Src\Application\Pharmacy\DTO\DecreaseBatchDTO;
use Src\Domain\Pharmacy\Entities\ProductBatch;
use Src\Domain\Pharmacy\Repositories\ProductBatchRepositoryInterface;

/**
 * Use case for decreasing stock from batches using FIFO (First Expiring, First Out).
 */
final class DecreaseBatchUseCase
{
    public function __construct(
        private ProductBatchRepositoryInterface $batchRepository
    ) {}

    /**
     * Execute the use case.
     * 
     * Decreases stock using FIFO based on expiration date.
     * The batch that expires first is consumed first.
     * 
     * @return array{
     *     decreased_from: array<array{batch_id: string, batch_number: string, quantity: int}>,
     *     total_decreased: int
     * }
     * 
     * @throws InvalidArgumentException if validation fails
     * @throws RuntimeException if insufficient stock
     */
    public function execute(DecreaseBatchDTO $dto): array
    {
        if ($dto->quantity <= 0) {
            throw new InvalidArgumentException('La quantité à retirer doit être supérieure à zéro.');
        }

        return DB::transaction(function () use ($dto) {
            // Get available batches sorted by expiration date (FIFO)
            $availableBatches = $this->batchRepository->findAvailableByProductFifo($dto->productId);

            if (empty($availableBatches)) {
                throw new RuntimeException('Aucun lot disponible pour ce produit.');
            }

            // Calculate total available stock
            $totalAvailable = array_reduce(
                $availableBatches,
                fn (int $carry, ProductBatch $batch) => $carry + $batch->getQuantity()->getValue(),
                0
            );

            if ($totalAvailable < $dto->quantity) {
                throw new RuntimeException(
                    sprintf(
                        'Stock insuffisant. Disponible: %d, Demandé: %d',
                        $totalAvailable,
                        $dto->quantity
                    )
                );
            }

            $remainingToDecrease = $dto->quantity;
            $decreasedFrom = [];
            $now = new DateTimeImmutable();

            foreach ($availableBatches as $batch) {
                if ($remainingToDecrease <= 0) {
                    break;
                }

                // Check if batch is expired
                if ($batch->isExpired($now)) {
                    if ($dto->blockIfExpired) {
                        throw new RuntimeException(
                            sprintf(
                                'Le lot %s est expiré (date: %s). Vente bloquée.',
                                $batch->getBatchNumber()->getValue(),
                                $batch->getExpirationDate()->format('d/m/Y')
                            )
                        );
                    }
                    // If not blocking, skip expired batches
                    continue;
                }

                $batchQuantity = $batch->getQuantity()->getValue();
                $toDecrease = min($batchQuantity, $remainingToDecrease);

                $batch->decreaseQuantity($toDecrease);
                $this->batchRepository->update($batch);

                $decreasedFrom[] = [
                    'batch_id' => $batch->getId(),
                    'batch_number' => $batch->getBatchNumber()->getValue(),
                    'quantity' => $toDecrease,
                    'expiration_date' => $batch->getExpirationDate()->format('Y-m-d'),
                ];

                $remainingToDecrease -= $toDecrease;
            }

            if ($remainingToDecrease > 0) {
                throw new RuntimeException(
                    sprintf(
                        'Stock insuffisant (lots non expirés). Restant à décrémenter: %d',
                        $remainingToDecrease
                    )
                );
            }

            return [
                'decreased_from' => $decreasedFrom,
                'total_decreased' => $dto->quantity,
            ];
        });
    }
}
