<?php

declare(strict_types=1);

namespace Src\Domain\Pharmacy\Repositories;

use Src\Domain\Pharmacy\Entities\SupplierProductPrice;

/**
 * Repository Interface: SupplierProductPriceRepositoryInterface
 *
 * Interface pour la persistence des prix fournisseur-produit.
 */
interface SupplierProductPriceRepositoryInterface
{
    public function save(SupplierProductPrice $price): void;

    public function update(SupplierProductPrice $price): void;

    public function findById(string $id): ?SupplierProductPrice;

    public function findBySupplierAndProduct(string $supplierId, string $productId): ?SupplierProductPrice;

    /**
     * Retourne tous les prix actifs pour un fournisseur.
     * @return SupplierProductPrice[]
     */
    public function findBySupplier(string $supplierId): array;

    /**
     * Retourne tous les prix actifs pour un produit (tous fournisseurs).
     * @return SupplierProductPrice[]
     */
    public function findByProduct(string $productId): array;

    /**
     * Retourne l'historique des prix pour une combinaison fournisseur-produit.
     * @return SupplierProductPrice[]
     */
    public function findHistoryBySupplierAndProduct(string $supplierId, string $productId): array;

    public function delete(string $id): void;
}
