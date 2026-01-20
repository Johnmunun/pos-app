<?php

namespace Domains\Tenant\Repositories;

use Domains\Tenant\Entities\Tenant;

/**
 * Repository Interface TenantRepository
 *
 * Définit le contrat pour accéder aux données des tenants.
 * Abstrait complètement la persistance (DB, cache, API, etc.).
 *
 * L'implémentation utilise Eloquent et est dans app/Repositories/EloquentTenantRepository.php
 *
 * Cette interface fait partie du DOMAIN et n'a aucune dépendance Laravel.
 */
interface TenantRepository
{
    /**
     * Trouver un tenant par son ID
     *
     * @param int $id
     * @return Tenant|null null si non trouvé
     */
    public function findById(int $id): ?Tenant;

    /**
     * Trouver un tenant par son code unique
     *
     * @param string $code
     * @return Tenant|null null si non trouvé
     */
    public function findByCode(string $code): ?Tenant;

    /**
     * Trouver un tenant par son email
     *
     * @param string $email
     * @return Tenant|null null si non trouvé
     */
    public function findByEmail(string $email): ?Tenant;

    /**
     * Obtenir tous les tenants (paginez côté application)
     *
     * @return Tenant[]
     */
    public function getAll(): array;

    /**
     * Obtenir les tenants actifs uniquement
     *
     * @return Tenant[]
     */
    public function getAllActive(): array;

    /**
     * Sauvegarder un tenant (création ou mise à jour)
     *
     * Après cette opération, le tenant aura un ID assigné.
     *
     * @param Tenant $tenant
     * @return Tenant L'entity persistée avec son ID
     *
     * @throws \Exception En cas d'erreur de persistance
     */
    public function save(Tenant $tenant): Tenant;

    /**
     * Supprimer un tenant par ID
     *
     * ⚠️ Suppression complète: effacera aussi toutes ses données associées
     *
     * @param int $id
     * @return bool true si supprimé, false si non trouvé
     */
    public function delete(int $id): bool;

    /**
     * Compter le nombre total de tenants
     *
     * @return int
     */
    public function count(): int;

    /**
     * Vérifier si un code existe
     *
     * @param string $code
     * @return bool
     */
    public function codeExists(string $code): bool;

    /**
     * Vérifier si un email existe
     *
     * @param string $email
     * @return bool
     */
    public function emailExists(string $email): bool;
}
