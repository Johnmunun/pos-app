<?php

declare(strict_types=1);

namespace Src\Domain\Pharmacy\Repositories;

use Src\Domain\Pharmacy\Entities\Supplier;

/**
 * Interface: SupplierRepositoryInterface
 *
 * Contrat pour la persistence des fournisseurs.
 */
interface SupplierRepositoryInterface
{
    /**
     * Sauvegarde un nouveau fournisseur.
     */
    public function save(Supplier $supplier): void;

    /**
     * Met à jour un fournisseur existant.
     */
    public function update(Supplier $supplier): void;

    /**
     * Trouve un fournisseur par son ID.
     */
    public function findById(string $id): ?Supplier;

    /**
     * Trouve un fournisseur par son nom dans une boutique (pour vérifier les doublons).
     */
    public function findByNameInShop(string $name, int $shopId): ?Supplier;

    /**
     * Récupère tous les fournisseurs d'une boutique.
     *
     * @return Supplier[]
     */
    public function findByShop(int $shopId): array;

    /**
     * Récupère les fournisseurs actifs d'une boutique.
     *
     * @return Supplier[]
     */
    public function findActiveByShop(int $shopId): array;

    /**
     * Supprime un fournisseur (soft delete via status).
     */
    public function delete(string $id): void;
}
