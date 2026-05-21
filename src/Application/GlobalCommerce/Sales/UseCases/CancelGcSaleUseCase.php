<?php

namespace Src\Application\GlobalCommerce\Sales\UseCases;

use Illuminate\Support\Facades\DB;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;
use Src\Domain\GlobalCommerce\Sales\Repositories\SaleRepositoryInterface;
use Src\Shared\ValueObjects\Quantity;

/**
 * Annule une vente Commerce (brouillon ou terminée).
 * Pour une vente terminée, le stock vendu est réintégré sur chaque ligne.
 */
final class CancelGcSaleUseCase
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly ProductRepositoryInterface $productRepository
    ) {
    }

    public function execute(string $saleId, string $shopId): void
    {
        DB::transaction(function () use ($saleId, $shopId): void {
            $sale = $this->saleRepository->findById($saleId);

            if (!$sale || $sale->getShopId() !== $shopId) {
                throw new \InvalidArgumentException('Vente introuvable.');
            }

            if ($sale->isCancelled()) {
                throw new \LogicException('Cette vente est déjà annulée.');
            }

            if ($sale->isCompleted()) {
                foreach ($sale->getLines() as $line) {
                    $quantity = (float) ($line['quantity'] ?? 0);
                    if ($quantity <= 0) {
                        continue;
                    }
                    $product = $this->productRepository->findById($line['product_id']);
                    if ($product && (string) $product->getShopId() === (string) $shopId) {
                        $product->addStock(new Quantity($quantity));
                        $this->productRepository->update($product);
                    }
                }
            }

            $sale->cancel();
            $this->saleRepository->save($sale);
        });
    }
}
