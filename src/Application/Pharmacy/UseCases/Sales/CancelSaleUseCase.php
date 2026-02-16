<?php

namespace Src\Application\Pharmacy\UseCases\Sales;

use Illuminate\Support\Facades\DB;
use Src\Domain\Pharmacy\Entities\Sale;
use Src\Domain\Pharmacy\Repositories\SaleRepositoryInterface;

/**
 * Use case : annuler une vente.
 *
 * Décision : pour l'instant, on n'effectue PAS de mouvement inverse de stock automatiquement
 * (l'annulation après encaissement/stock sorti sera gérée plus tard via un processus d'avoir/retour).
 */
class CancelSaleUseCase
{
    public function __construct(
        private SaleRepositoryInterface $saleRepository
    ) {}

    public function execute(string $saleId): void
    {
        DB::transaction(function () use ($saleId): void {
            $sale = $this->saleRepository->findById($saleId);

            if (!$sale) {
                throw new \InvalidArgumentException('Sale not found');
            }

            if ($sale->getStatus() === Sale::STATUS_COMPLETED) {
                throw new \LogicException('Completed sales cannot be cancelled directly');
            }

            $sale->cancel();
            $this->saleRepository->save($sale);
        });
    }
}

