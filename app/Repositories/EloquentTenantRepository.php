<?php

namespace App\Repositories;

use App\Models\Tenant as TenantModel;
use Domains\Tenant\Entities\Tenant;
use Domains\Tenant\Repositories\TenantRepository;

/**
 * Repository Implementation: EloquentTenantRepository
 *
 * Implémente l'interface TenantRepository du domain avec Eloquent.
 *
 * Responsabilités:
 * - Traduire les opérations domain en requêtes Eloquent
 * - Hydrater les entities depuis la DB
 * - Convertir les entities en models pour persistence
 *
 * Cette classe est dans l'INFRASTRUCTURE (dépend de Laravel/Eloquent).
 * Elle implémente l'interface du domain (couplage à sens unique).
 */
class EloquentTenantRepository implements TenantRepository
{
    /**
     * Trouver un tenant par ID
     *
     * @param int $id
     * @return Tenant|null
     */
    public function findById(int $id): ?Tenant
    {
        // Requête Eloquent
        $model = TenantModel::find($id);

        // Convertir en entity domain si trouvé
        return $model ? $this->modelToEntity($model) : null;
    }

    /**
     * Trouver un tenant par code
     *
     * @param string $code
     * @return Tenant|null
     */
    public function findByCode(string $code): ?Tenant
    {
        $model = TenantModel::findByCode($code);
        return $model ? $this->modelToEntity($model) : null;
    }

    /**
     * Trouver un tenant par email
     *
     * @param string $email
     * @return Tenant|null
     */
    public function findByEmail(string $email): ?Tenant
    {
        $model = TenantModel::findByEmail($email);
        return $model ? $this->modelToEntity($model) : null;
    }

    /**
     * Obtenir tous les tenants
     *
     * @return Tenant[]
     */
    public function getAll(): array
    {
        return TenantModel::all()
            ->map(fn($model) => $this->modelToEntity($model))
            ->toArray();
    }

    /**
     * Obtenir tous les tenants actifs
     *
     * @return Tenant[]
     */
    public function getAllActive(): array
    {
        return TenantModel::active()
            ->get()
            ->map(fn($model) => $this->modelToEntity($model))
            ->toArray();
    }

    /**
     * Sauvegarder un tenant (création ou mise à jour)
     *
     * Processus:
     * 1. Convertir l'entity en tableau de données
     * 2. Créer ou mettre à jour le model
     * 3. Assigner l'ID à l'entity
     * 4. Retourner l'entity persistée
     *
     * @param Tenant $tenant
     * @return Tenant L'entity persistée avec ID
     */
    public function save(Tenant $tenant): Tenant
    {
        // Préparer les données du model
        $data = [
            'code'      => $tenant->getCode()->getValue(),
            'name'      => $tenant->getName()->getValue(),
            'email'     => $tenant->getEmail()->getValue(),
            'is_active' => $tenant->isActive(),
        ];

        // Créer ou mettre à jour
        if ($tenant->getId()) {
            // Mise à jour
            $model = TenantModel::findOrFail($tenant->getId());
            $model->update($data);
        } else {
            // Création
            $model = TenantModel::create($data);
            // Assigner l'ID généré à l'entity
            $tenant->setId($model->id);
        }

        return $tenant;
    }

    /**
     * Supprimer un tenant
     *
     * ⚠️ ATTENTION: Cette opération est irréversible et supprimera aussi
     * toutes les données associées (via cascade delete en DB)
     *
     * @param int $id
     * @return bool true si supprimé, false si non trouvé
     */
    public function delete(int $id): bool
    {
        $model = TenantModel::find($id);

        if (!$model) {
            return false;
        }

        $model->delete();
        return true;
    }

    /**
     * Compter le nombre total de tenants
     *
     * @return int
     */
    public function count(): int
    {
        return TenantModel::count();
    }

    /**
     * Vérifier si un code existe
     *
     * @param string $code
     * @return bool
     */
    public function codeExists(string $code): bool
    {
        return TenantModel::codeExists($code);
    }

    /**
     * Vérifier si un email existe
     *
     * @param string $email
     * @return bool
     */
    public function emailExists(string $email): bool
    {
        return TenantModel::emailExists($email);
    }

    /**
     * Helper: Convertir un Model Eloquent en Entity Domain
     *
     * Hydrate une entity à partir des données du model.
     * Cette méthode traduit les données "plates" de la DB
     * en objet domain riche.
     *
     * @param TenantModel $model
     * @return Tenant
     */
    private function modelToEntity(TenantModel $model): Tenant
    {
        return Tenant::hydrate(
            id: $model->id,
            code: $model->code,
            name: $model->name,
            email: $model->email,
            isActive: $model->is_active,
            createdAt: $model->created_at ?? new \DateTime(),
            updatedAt: $model->updated_at
        );
    }

    /**
     * Helper: Convertir une Entity en données Model (array)
     *
     * Transforme l'objet domain riche en données plates.
     * Utilisé pour la persistance.
     *
     * @param Tenant $entity
     * @return array
     */
    private function entityToArray(Tenant $entity): array
    {
        return [
            'code'      => $entity->getCode()->getValue(),
            'name'      => $entity->getName()->getValue(),
            'email'     => $entity->getEmail()->getValue(),
            'is_active' => $entity->isActive(),
        ];
    }
}
