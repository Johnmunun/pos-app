<?php

namespace Src\Domain\Currency\Repositories;

use Src\Domain\Currency\Entities\Currency;

/**
 * Repository Interface: CurrencyRepositoryInterface
 * 
 * Définit le contrat pour la persistance des devises
 */
interface CurrencyRepositoryInterface
{
    /**
     * Trouver une devise par ID
     */
    public function findById(int $id): ?Currency;

    /**
     * Trouver toutes les devises d'un tenant
     * 
     * @return Currency[]
     */
    public function findByTenantId(int $tenantId): array;

    /**
     * Trouver la devise par défaut d'un tenant
     */
    public function findDefaultByTenantId(int $tenantId): ?Currency;

    /**
     * Trouver une devise par code et tenant
     */
    public function findByCodeAndTenant(string $code, int $tenantId): ?Currency;

    /**
     * Sauvegarder (créer ou mettre à jour) une devise
     */
    public function save(Currency $currency): void;

    /**
     * Supprimer une devise
     */
    public function delete(Currency $currency): void;

    /**
     * Désactiver toutes les devises par défaut d'un tenant
     */
    public function unsetAllDefaultsForTenant(int $tenantId): void;
}
