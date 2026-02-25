<?php

namespace Src\Domain\Finance\Repositories;

/**
 * Port pour la fourniture des données de ventes complétées (calcul bénéfice).
 * Implémenté en Infrastructure par une requête indexée (shop_id, status, completed_at).
 * Évite les requêtes lourdes et la dépendance directe au module Pharmacy dans le Domain.
 */
interface ProfitDataProviderInterface
{
    /**
     * Lignes de ventes complétées pour une boutique et une période.
     * Chaque élément: product_id, quantity, unit_price, currency, sale_id.
     * @return array<int, array{product_id: string, quantity: int, unit_price: float, currency: string, product_name: string, unit_cost: float}>
     */
    public function getCompletedSaleLinesForPeriod(string $shopId, ?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null): array;
}
