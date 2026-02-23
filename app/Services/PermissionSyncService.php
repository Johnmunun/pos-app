<?php

namespace App\Services;

use Src\Domains\User\Services\PermissionsSyncService as DomainPermissionsSyncService;

/**
 * Service wrapper pour PermissionSyncService
 * 
 * Alias vers Src\Domains\User\Services\PermissionsSyncService
 * pour compatibilité avec les seeders et tests existants.
 */
class PermissionSyncService
{
    public function __construct(
        private readonly DomainPermissionsSyncService $domainService
    ) {}

    /**
     * Synchronise les permissions depuis le fichier par défaut (YAML)
     * 
     * Alias de syncFromYaml() pour compatibilité
     * 
     * @return array{created: int, updated: int, marked_old: int, deleted: int, errors: array<int, string>, total_in_yaml: int, total_in_db: int}
     */
    public function syncFromDefaultFile(): array
    {
        $result = $this->domainService->syncFromYaml();
        
        // Adapter le format de retour pour correspondre aux attentes du seeder
        return [
            'created' => $result['created'],
            'updated' => $result['updated'],
            'marked_old' => $result['deleted'], // Les permissions marquées comme obsolètes correspondent à "deleted"
            'deleted' => $result['deleted'],
            'errors' => $result['errors'],
            'total_in_yaml' => $result['total_in_yaml'],
            'total_in_db' => $result['total_in_db'],
        ];
    }
}
