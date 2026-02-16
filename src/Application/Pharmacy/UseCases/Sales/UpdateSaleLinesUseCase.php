<?php

namespace Src\Application\Pharmacy\UseCases\Sales;

use Illuminate\Support\Facades\DB;
use Src\Application\Pharmacy\DTO\SaleLineDTO;
use Src\Domain\Pharmacy\Entities\SaleLine;
use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\SaleLineRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\SaleRepositoryInterface;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;

/**
 * Use case : mettre à jour le panier (lignes de vente) d'une vente en statut DRAFT.
 *
 * Décision de simplification : on remplace l'ensemble des lignes par la liste fournie.
 */
class UpdateSaleLinesUseCase
{
    public function __construct(
        private SaleRepositoryInterface $saleRepository,
        private SaleLineRepositoryInterface $saleLineRepository,
        private ProductRepositoryInterface $productRepository
    ) {}

    /**
     * @param string $saleId
     * @param SaleLineDTO[] $lines
     */
    public function execute(string $saleId, array $lines): void
    {
        DB::transaction(function () use ($saleId, $lines): void {
            $sale = $this->saleRepository->findById($saleId);

            if (!$sale) {
                throw new \InvalidArgumentException('Sale not found');
            }

            if ($sale->getStatus() !== \Src\Domain\Pharmacy\Entities\Sale::STATUS_DRAFT) {
                throw new \LogicException('Only draft sales can be updated');
            }

            // On supprime les lignes existantes et on les remplace par les nouvelles
            $this->saleLineRepository->deleteBySale($saleId);

            $currency = $sale->getCurrency();
            $total = new Money(0, $currency);

            foreach ($lines as $lineDto) {
                // Vérifier que le produit existe
                $product = $this->productRepository->findById($lineDto->productId);
                if (!$product) {
                    throw new \InvalidArgumentException('Product not found for line');
                }

                $quantity = new Quantity($lineDto->quantity);
                $unitPrice = new Money($lineDto->unitPrice, $currency);

                $line = SaleLine::create(
                    $saleId,
                    $lineDto->productId,
                    $quantity,
                    $unitPrice,
                    $lineDto->discountPercent
                );

                $this->saleLineRepository->save($line);
                $total = $total->add($line->getLineTotal());
            }

            // On laisse le paidAmount/balance gérés à la finalisation
            $sale->updateTotals($total, new Money($sale->getPaidAmount()->getAmount(), $currency));
            $this->saleRepository->save($sale);
        });
    }
}

