<?php

namespace Src\Domains\User\Services;

use App\Models\Permission;
use Illuminate\Support\Facades\DB;

/**
 * Service de synchronisation des permissions
 * 
 * Responsable de la synchronisation des permissions depuis le fichier YAML
 * vers la base de données, en respectant l'architecture DDD.
 */
class PermissionsSyncService
{
    /**
     * Synchronise les permissions depuis le fichier YAML vers la base de données
     * 
     * @return array{created: int, updated: int, deleted: int, errors: array<int, string>, total_in_yaml: int, total_in_db: int} Rapport de synchronisation
     */
    public function syncFromYaml(): array
    {
        $report = [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'errors' => [],
            'total_in_yaml' => 0,
            'total_in_db' => 0
        ];

        try {
            // Lire le fichier YAML directement depuis le chemin (plus fiable que Storage facade)
            $yamlPath = storage_path('app/permissions.yaml');
            
            if (!file_exists($yamlPath)) {
                $report['errors'][] = 'Fichier storage/app/permissions.yaml introuvable. Créez ce fichier ou synchronisez depuis la base.';
                $report['total_in_db'] = Permission::count();
                return $report;
            }
            
            $yamlContent = file_get_contents($yamlPath);
            if ($yamlContent === false) {
                $yamlContent = '';
                $report['errors'][] = 'Impossible de lire le fichier storage/app/permissions.yaml.';
            }
            $permissionsData = $this->parseYaml($yamlContent);
            
            // Extraire toutes les permissions du YAML
            $yamlPermissions = $this->extractPermissionsFromYaml($permissionsData);
            $report['total_in_yaml'] = count($yamlPermissions);
            
            // Si aucun contenu YAML, ne pas modifier la base (éviter de marquer toutes les permissions comme obsolètes)
            if ($report['total_in_yaml'] === 0) {
                $report['total_in_db'] = Permission::count();
                return $report;
            }
            
            // Obtenir le nombre de permissions actuelles de la base de données
            $report['total_in_db'] = Permission::count();
            
            // Commencer une transaction
            DB::beginTransaction();
            
            // Créer ou mettre à jour les permissions
            foreach ($yamlPermissions as $permissionCode) {
                $existingPermission = Permission::where('code', $permissionCode)->first();
                
                if ($existingPermission) {
                    // Permission existe déjà, on la met à jour si nécessaire
                    $existingPermission->update([
                        'is_old' => false,
                        'description' => $this->generateDescription($permissionCode)
                    ]);
                    $report['updated']++;
                } else {
                    // Créer une nouvelle permission
                    Permission::create([
                        'code' => $permissionCode,
                        'description' => $this->generateDescription($permissionCode),
                        'is_old' => false
                    ]);
                    $report['created']++;
                }
            }
            
            // Marquer les permissions qui ne sont plus dans le YAML comme obsolètes
            $yamlPermissionCodes = array_values($yamlPermissions);
            $permissionsToDelete = Permission::whereNotIn('code', $yamlPermissionCodes)
                ->where('is_old', false)
                ->get();
                
            foreach ($permissionsToDelete as $permission) {
                $permission->update(['is_old' => true]);
                $report['deleted']++;
            }
            
            // Commit la transaction
            DB::commit();
            
        } catch (\Exception $e) {
            // Rollback en cas d'erreur
            DB::rollBack();
            $report['errors'][] = $e->getMessage();
        }
        
        return $report;
    }
    
    /**
     * Parse un fichier YAML simple
     * 
     * @param string|null $yamlContent Contenu du fichier YAML (null = vide)
     * @return array<string, array<int, string>> Données parsées (groupe => liste de permissions)
     */
    private function parseYaml(?string $yamlContent): array
    {
        $lines = explode("\n", $yamlContent ?? '');
        $result = [];
        $currentGroup = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Ignorer les commentaires et lignes vides
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            
            // Détection de groupe (lignes se terminant par ':')
            if (str_ends_with($line, ':')) {
                $currentGroup = rtrim($line, ':');
                $result[$currentGroup] = [];
                continue;
            }
            
            // Extraction des permissions (lignes commençant par '-')
            if (str_starts_with($line, '-') && $currentGroup) {
                $permission = trim(substr($line, 1));
                if (!empty($permission)) {
                    $result[$currentGroup][] = $permission;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Extrait toutes les permissions du tableau YAML
     * 
     * @param array<string, array<int, string>> $yamlData Données YAML parsées
     * @return array<int, string> Liste des codes de permissions
     */
    private function extractPermissionsFromYaml(array $yamlData): array
    {
        $permissions = [];
        
        foreach ($yamlData as $group => $groupPermissions) {
            if (is_array($groupPermissions)) {
                foreach ($groupPermissions as $permission) {
                    if (is_string($permission)) {
                        $permission = trim($permission);
                        if (empty($permission)) {
                            continue;
                        }
                        
                        // Si la permission contient déjà un point, elle est déjà complète
                        // Vérifier si elle commence par le groupe ou un autre préfixe complet
                        if (str_contains($permission, '.')) {
                            // Permission déjà complète (ex: admin.users.view, module.pharmacy)
                            $permissionCode = $permission;
                        } elseif (str_starts_with($permission, $group . '.')) {
                            // Permission commence déjà par le groupe (redondant mais géré)
                            $permissionCode = $permission;
                        } else {
                            // Permission simple, ajouter le préfixe du groupe
                            $permissionCode = $group . '.' . $permission;
                        }
                        $permissions[] = $permissionCode;
                    }
                }
            }
        }
        
        return array_unique($permissions);
    }
    
    /**
     * Méthode de test pour vérifier le parsing YAML
     * 
     * @return bool True si le parsing fonctionne correctement
     */
    public function testYamlParsing(): bool
    {
        $testYaml = "admin:
  - access.manage
  - access.permissions.delete
general:
  - dashboard.view";
        
        $parsed = $this->parseYaml($testYaml);
        $expected = [
            'admin' => ['access.manage', 'access.permissions.delete'],
            'general' => ['dashboard.view']
        ];
        
        return $parsed === $expected;
    }
    
    /**
     * Génère une description automatique pour une permission
     * 
     * @param string $code Code de la permission
     * @return string Description générée
     */
    private function generateDescription(string $code): string
    {
        $parts = explode('.', $code);
        if (empty($parts)) {
            return ucfirst($code);
        }
        
        $action = end($parts);
        if ($action === false) {
            $action = '';
        }
        $module = $parts[0] ?? 'general';
        
        // Pour les permissions multi-niveaux (ex: pharmacy.category.view)
        $resource = count($parts) > 2 && isset($parts[1]) ? $parts[1] : null;
        
        $actionLabels = [
            'view' => 'Voir',
            'create' => 'Créer',
            'update' => 'Modifier',
            'delete' => 'Supprimer',
            'manage' => 'Gérer',
            'search' => 'Rechercher',
            'sync' => 'Synchroniser',
            'export' => 'Exporter'
        ];
        
        $moduleLabels = [
            'admin' => 'Administration',
            'general' => 'Général',
            'commerce' => 'Commerce',
            'products' => 'Produits',
            'payments' => 'Paiements',
            'support' => 'Support',
            'reports' => 'Rapports',
            'logs' => 'Journaux',
            'modules' => 'Modules',
            'pharmacy' => 'Pharmacie',
            'butchery' => 'Boucherie',
            'kiosk' => 'Kiosque',
            'supermarket' => 'Supermarché',
            'hardware' => 'Quincaillerie',
            'categories' => 'Catégories',
            'settings' => 'Paramètres'
        ];
        
        $resourceLabels = [
            'category' => 'Catégorie',
            'categories' => 'Catégories',
            'product' => 'Produit',
            'products' => 'Produits',
            'batch' => 'Lot',
            'batches' => 'Lots',
            'medicine' => 'Médicament',
            'medicines' => 'Médicaments',
            'prescription' => 'Ordonnance',
            'prescriptions' => 'Ordonnances',
            'supplier' => 'Fournisseur',
            'suppliers' => 'Fournisseurs',
            'stock' => 'Stock',
            'sale' => 'Vente',
            'sales' => 'Ventes',
            'report' => 'Rapport',
            'reports' => 'Rapports',
            'expiry' => 'Expiration',
            'currency' => 'Devise',
            'currencies' => 'Devises'
        ];
        
        $actionLabel = $actionLabels[$action] ?? ucfirst($action);
        $moduleLabel = $moduleLabels[$module] ?? ucfirst($module);
        
        // Si on a une ressource spécifique, l'inclure dans la description
        if ($resource && isset($resourceLabels[$resource])) {
            $resourceLabel = $resourceLabels[$resource];
            return "$actionLabel $resourceLabel ($moduleLabel)";
        }
        
        return "$actionLabel $moduleLabel";
    }
}