<?php

namespace Src\Domain\Pharmacy\Repositories;

use DateTimeImmutable;
use Src\Domain\Pharmacy\Entities\StockTransfer;

interface StockTransferRepositoryInterface
{
    /**
     * Sauvegarde un transfert
     */
    public function save(StockTransfer $transfer): void;

    /**
     * Met à jour un transfert
     */
    public function update(StockTransfer $transfer): void;

    /**
     * Trouve un transfert par son ID
     */
    public function findById(string $id): ?StockTransfer;

    /**
     * Trouve un transfert par ID et pharmacy_id (multi-tenant)
     */
    public function findByIdAndPharmacy(string $id, string $pharmacyId): ?StockTransfer;

    /**
     * Trouve un transfert par sa référence
     */
    public function findByReference(string $reference, string $pharmacyId): ?StockTransfer;

    /**
     * Liste les transferts d'une pharmacie avec filtres
     * 
     * @param string $pharmacyId
     * @param array<string, mixed> $filters (status, from_shop_id, to_shop_id, from, to)
     * @return StockTransfer[]
     */
    public function findByPharmacy(string $pharmacyId, array $filters = []): array;

    /**
     * Liste tous les transferts (pour ROOT)
     * 
     * @param array<string, mixed> $filters
     * @return StockTransfer[]
     */
    public function findAll(array $filters = []): array;

    /**
     * Supprime un transfert
     */
    public function delete(string $id): void;
}
