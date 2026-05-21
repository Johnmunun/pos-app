<?php

namespace Src\Application\Pharmacy\Services;

use DateTimeImmutable;
use Src\Domain\Pharmacy\Entities\Batch;
use Src\Domain\Pharmacy\Repositories\BatchRepositoryInterface;
use Src\Domain\Pharmacy\ValueObjects\ExpiryDate;
use Src\Infrastructure\Pharmacy\Models\BatchModel;
use Src\Shared\ValueObjects\Quantity;

/**
 * Aligne le stock produit (pharmacy_products.stock) avec des lots FIFO exploitables.
 * Corrige les imports boutique pré-configurée qui posent le stock sans lot.
 */
final class OpeningBatchAligner
{
    public function __construct(
        private BatchRepositoryInterface $batchRepository
    ) {}

    public function ensureProductStockCoveredByBatches(
        string $shopId,
        string $productId,
        float $productStock,
        ?int $depotId = null,
        string $batchNumberPrefix = 'INIT'
    ): void {
        $productStock = round($productStock, 4);
        if ($productStock <= 0) {
            return;
        }

        $batchTotal = 0.0;
        foreach ($this->batchRepository->findByProduct($productId) as $batch) {
            $batchTotal += $batch->getQuantity()->getValue();
        }
        $batchTotal = round($batchTotal, 4);

        $gap = round($productStock - $batchTotal, 4);
        if ($gap <= 0.0001) {
            return;
        }

        $batchNumber = $this->buildUniqueBatchNumber($shopId, $productId, $batchNumberPrefix);
        $expiry = new ExpiryDate(new DateTimeImmutable('+2 years'));
        $batch = Batch::create(
            $shopId,
            $productId,
            $batchNumber,
            $expiry,
            new Quantity($gap)
        );

        BatchModel::query()->create([
            'id' => $batch->getId(),
            'shop_id' => $shopId,
            'depot_id' => $depotId,
            'product_id' => $productId,
            'batch_number' => $batchNumber,
            'expiry_date' => $expiry->getDate()->format('Y-m-d'),
            'quantity' => $gap,
            'initial_quantity' => $gap,
        ]);
    }

    private function buildUniqueBatchNumber(string $shopId, string $productId, string $prefix): string
    {
        $base = strtoupper($prefix.'-'.substr(str_replace('-', '', $productId), 0, 8));
        $candidate = $base;
        $suffix = 1;

        while ($this->batchRepository->findByBatchNumber($candidate, $shopId) !== null) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
