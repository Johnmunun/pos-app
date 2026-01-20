<?php

namespace Domains\Tenant\Services;

use Domains\Tenant\Entities\Tenant;
use Domains\Tenant\Repositories\TenantRepository;
use Domains\Tenant\Exceptions\DuplicateTenantCodeException;
use Domains\Tenant\Exceptions\InvalidTenantStateException;

/**
 * Domain Service: TenantService
 *
 * Encapsule TOUTE la logique métier du domaine Tenant.
 * Orchestre les opérations entre les entities, value objects et repositories.
 *
 * Cette classe n'a AUCUNE dépendance Laravel.
 * Elle ne parle que le langage métier.
 *
 * Responsabilités:
 * - Créer de nouveaux tenants
 * - Valider les règles métier
 * - Modifier les tenants
 * - Vérifier l'unicité des codes
 */
class TenantService
{
    /**
     * Injection du repository
     *
     * @param TenantRepository $repository
     */
    public function __construct(
        private TenantRepository $repository
    ) {}

    /**
     * Créer un nouveau tenant avec validation métier
     *
     * Processus:
     * 1. Valider l'unicité du code
     * 2. Créer l'entity Tenant
     * 3. Persister en base de données
     * 4. Retourner l'entity persistée
     *
     * @param string $code Code unique du tenant
     * @param string $name Nom commercial
     * @param string $email Email de contact
     *
     * @return Tenant L'entity persistée avec ID
     *
     * @throws DuplicateTenantCodeException Si le code existe déjà
     * @throws \InvalidArgumentException Si une validation échoue
     * @throws \Exception En cas d'erreur de persistance
     */
    public function createTenant(
        string $code,
        string $name,
        string $email
    ): Tenant {
        // Vérifier l'unicité du code AVANT de créer l'entity
        if ($this->repository->codeExists($code)) {
            throw DuplicateTenantCodeException::withCode($code);
        }

        // Créer la nouvelle entity (validation automatique des value objects)
        $tenant = Tenant::createNew($code, $name, $email);

        // Persister en base de données
        return $this->repository->save($tenant);
    }

    /**
     * Activer un tenant
     *
     * @param int $tenantId
     * @return Tenant Le tenant activé
     *
     * @throws InvalidTenantStateException Si le tenant est déjà actif ou non trouvé
     */
    public function activateTenant(int $tenantId): Tenant
    {
        $tenant = $this->repository->findById($tenantId);

        if (!$tenant) {
            throw InvalidTenantStateException::notFound($tenantId);
        }

        // La logique d'activation est dans l'entity
        $tenant->activate();

        return $this->repository->save($tenant);
    }

    /**
     * Désactiver un tenant
     *
     * @param int $tenantId
     * @return Tenant Le tenant désactivé
     *
     * @throws InvalidTenantStateException Si le tenant est déjà inactif ou non trouvé
     */
    public function deactivateTenant(int $tenantId): Tenant
    {
        $tenant = $this->repository->findById($tenantId);

        if (!$tenant) {
            throw InvalidTenantStateException::notFound($tenantId);
        }

        // La logique de désactivation est dans l'entity
        $tenant->deactivate();

        return $this->repository->save($tenant);
    }

    /**
     * Mettre à jour les infos d'un tenant
     *
     * @param int $tenantId
     * @param string|null $name Nouveau nom (optionnel)
     * @param string|null $email Nouvel email (optionnel)
     *
     * @return Tenant Le tenant mis à jour
     *
     * @throws InvalidTenantStateException Si le tenant n'existe pas
     * @throws \InvalidArgumentException Si les nouvelles valeurs ne sont pas valides
     */
    public function updateTenant(
        int $tenantId,
        ?string $name = null,
        ?string $email = null
    ): Tenant {
        $tenant = $this->repository->findById($tenantId);

        if (!$tenant) {
            throw InvalidTenantStateException::notFound($tenantId);
        }

        // Mettre à jour uniquement les champs fournis
        if ($name !== null) {
            $tenant->updateName($name);
        }

        if ($email !== null) {
            $tenant->updateEmail($email);
        }

        return $this->repository->save($tenant);
    }

    /**
     * Récupérer les informations complètes d'un tenant
     *
     * @param int $tenantId
     * @return Tenant
     *
     * @throws InvalidTenantStateException Si le tenant n'existe pas
     */
    public function getTenant(int $tenantId): Tenant
    {
        $tenant = $this->repository->findById($tenantId);

        if (!$tenant) {
            throw InvalidTenantStateException::notFound($tenantId);
        }

        return $tenant;
    }

    /**
     * Récupérer un tenant par son code
     *
     * Utile pour les URLs avec le code au lieu de l'ID
     *
     * @param string $code
     * @return Tenant
     *
     * @throws \Exception Si le tenant n'existe pas
     */
    public function getTenantByCode(string $code): Tenant
    {
        $tenant = $this->repository->findByCode($code);

        if (!$tenant) {
            throw new \Exception("Tenant with code '{$code}' not found");
        }

        return $tenant;
    }

    /**
     * Lister tous les tenants actifs
     *
     * @return Tenant[]
     */
    public function listAllActiveTenants(): array
    {
        return $this->repository->getAllActive();
    }

    /**
     * Supprimer un tenant
     *
     * ⚠️ Opération irréversible
     *
     * @param int $tenantId
     * @return bool true si supprimé, false si non trouvé
     */
    public function deleteTenant(int $tenantId): bool
    {
        return $this->repository->delete($tenantId);
    }
}
