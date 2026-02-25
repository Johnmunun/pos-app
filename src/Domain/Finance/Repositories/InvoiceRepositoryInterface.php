<?php

namespace Src\Domain\Finance\Repositories;

use Src\Domain\Finance\Entities\Invoice;

interface InvoiceRepositoryInterface
{
    public function save(Invoice $invoice): void;

    public function findById(string $id): ?Invoice;

    public function findByNumber(string $number, string $shopId): ?Invoice;

    /**
     * Dernier numéro de séquence pour la boutique (année + préfixe). Index unique sur (shop_id, number).
     * Requête simple indexée, pas d'agrégation lourde.
     */
    public function getLastSequenceForShop(string $shopId, string $prefix, string $year): int;

    /**
     * @param array{status?: string, source_type?: string, from?: string, to?: string} $filters
     * @return array{items: Invoice[], total: int}
     */
    public function findByTenantPaginated(string $tenantId, int $perPage, int $page, array $filters = []): array;
}
