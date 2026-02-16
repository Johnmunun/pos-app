<?php

namespace Src\Domain\Pharmacy\Repositories;

use DateTimeImmutable;
use Src\Domain\Pharmacy\Entities\StockMovement;

interface StockMovementRepositoryInterface
{
    public function save(StockMovement $movement): void;

    /**
     * @return StockMovement[]
     */
    public function findByProduct(string $productId, string $shopId): array;

    /**
     * Recherche les mouvements de stock avec filtres avancés
     *
     * @param string $shopId
     * @param array<string, mixed> $filters (product_id, product_code, type, from, to)
     * @return StockMovement[]
     */
    public function findByShopWithFilters(string $shopId, array $filters = []): array;

    /**
     * Trouve un mouvement par son ID
     *
     * @param string $id
     * @param string $shopId
     * @return StockMovement|null
     */
    public function findById(string $id, string $shopId): ?StockMovement;

    /**
     * Compte les mouvements pour un shop avec filtres
     *
     * @param string $shopId
     * @param array<string, mixed> $filters
     * @return int
     */
    public function countByShop(string $shopId, array $filters = []): int;
}

