<?php

namespace Src\Domain\Finance\Services;

use Src\Domain\Finance\Repositories\InvoiceRepositoryInterface;

/**
 * Domain Service : génération du numéro de facture (sécurisée, unique).
 * Pas de requêtes agrégées complexes : lecture du dernier numéro indexé.
 */
final class InvoiceNumberGeneratorService
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository
    ) {}

    public function generateNext(string $shopId, string $prefix = 'INV'): string
    {
        $year = date('Y');
        $lastNumber = $this->invoiceRepository->getLastSequenceForShop($shopId, $prefix, $year);
        $next = $lastNumber + 1;
        return sprintf('%s-%s-%04d', $prefix, $year, $next);
    }
}
