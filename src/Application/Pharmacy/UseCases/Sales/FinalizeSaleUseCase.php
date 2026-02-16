<?php

namespace Src\Application\Pharmacy\UseCases\Sales;

use Illuminate\Support\Facades\DB;
use Src\Domain\Pharmacy\Entities\Sale;
use Src\Domain\Pharmacy\Repositories\SaleLineRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\SaleRepositoryInterface;
use Src\Application\Pharmacy\UseCases\Inventory\UpdateStockUseCase;
use Src\Shared\ValueObjects\Money;

/**
 * Use case : finaliser une vente (validation du panier, paiement, mise à jour du stock).
 */
class FinalizeSaleUseCase
{
    public function __construct(
        private SaleRepositoryInterface $saleRepository,
        private SaleLineRepositoryInterface $saleLineRepository,
        private UpdateStockUseCase $updateStockUseCase
    ) {}

    /**
     * @param string $saleId
     * @param float $paidAmount
     * @param int $userId utilisé pour created_by des mouvements de stock
     */
    public function execute(string $saleId, float $paidAmount, int $userId): void
    {
        DB::transaction(function () use ($saleId, $paidAmount, $userId): void {
            $sale = $this->saleRepository->findById($saleId);

            if (!$sale) {
                throw new \InvalidArgumentException('Sale not found');
            }

            if ($sale->getStatus() !== Sale::STATUS_DRAFT) {
                throw new \LogicException('Only draft sales can be finalized');
            }

            $lines = $this->saleLineRepository->findBySale($saleId);
            if (count($lines) === 0) {
                throw new \LogicException('Cannot finalize empty sale');
            }

            $currency = $sale->getCurrency();
            $total = new Money(0, $currency);

            foreach ($lines as $line) {
                $total = $total->add($line->getLineTotal());
            }

            $paid = new Money($paidAmount, $currency);

            if ($paid->getAmount() < 0) {
                throw new \InvalidArgumentException('Paid amount cannot be negative');
            }

            // Mettre à jour les totaux (le solde représente la partie crédit éventuelle)
            $sale->updateTotals($total, $paid);

            // Mise à jour du stock pour chaque produit
            foreach ($lines as $line) {
                $this->updateStockUseCase->removeStock(
                    $line->getProductId(),
                    $line->getQuantity()->getValue(),
                    $sale->getShopId(),
                    $userId,
                    'SALE-' . $saleId
                );
            }

            $sale->markCompleted();
            $this->saleRepository->save($sale);
        });
    }
}

