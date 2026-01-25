<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Services\PermissionSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestPermissionSync extends Command
{
    protected $signature = 'permissions:test-sync';
    protected $description = 'Tester la synchronisation des permissions';

    public function handle()
    {
        $this->info('🔍 Test de synchronisation des permissions...');
        $this->newLine();

        // Lire le fichier YAML
        $yamlPath = 'permissions.yaml';
        if (!Storage::disk('local')->exists($yamlPath)) {
            $this->error("❌ Le fichier {$yamlPath} n'existe pas dans storage/app/");
            return 1;
        }

        $content = Storage::disk('local')->get($yamlPath);
        $syncService = new PermissionSyncService();
        
        // Parser les permissions
        $reflection = new \ReflectionClass($syncService);
        $method = $reflection->getMethod('parsePermissions');
        $method->setAccessible(true);
        $parsedPermissions = $method->invoke($syncService, $content);

        $this->info("📊 Permissions parsées depuis le YAML: " . count($parsedPermissions));
        $this->newLine();

        // Afficher les 10 premières permissions
        $this->info("📋 Exemples de permissions parsées:");
        $count = 0;
        foreach ($parsedPermissions as $code => $group) {
            if ($count++ >= 10) break;
            $this->line("   • {$code} (groupe: {$group})");
        }
        $this->newLine();
        
        // Vérifier spécifiquement les permissions de catégories
        $categoryPerms = array_filter($parsedPermissions, function($code) {
            return str_starts_with($code, 'categories.');
        }, ARRAY_FILTER_USE_KEY);
        
        if (!empty($categoryPerms)) {
            $this->info("✅ Permissions de catégories trouvées dans le YAML:");
            foreach ($categoryPerms as $code => $group) {
                $this->line("   • {$code} (groupe: {$group})");
            }
        } else {
            $this->warn("⚠️  Aucune permission de catégories trouvée dans le YAML parsé!");
        }
        $this->newLine();

        // Vérifier les permissions dans la DB
        $dbPermissions = Permission::all();
        $this->info("📊 Permissions dans la base de données: " . $dbPermissions->count());
        $this->info("   • Actives (is_old = false): " . $dbPermissions->where('is_old', false)->count());
        $this->info("   • Obsolètes (is_old = true): " . $dbPermissions->where('is_old', true)->count());
        $this->newLine();

        // Vérifier les permissions manquantes
        $yamlCodes = array_keys($parsedPermissions);
        $dbCodes = $dbPermissions->pluck('code')->toArray();
        $missingInDb = array_diff($yamlCodes, $dbCodes);
        $missingInYaml = array_diff($dbCodes, $yamlCodes);

        if (!empty($missingInDb)) {
            $this->warn("⚠️  Permissions dans YAML mais ABSENTES de la DB (" . count($missingInDb) . "):");
            foreach (array_slice($missingInDb, 0, 10) as $code) {
                $this->line("   • {$code}");
            }
            if (count($missingInDb) > 10) {
                $this->line("   ... et " . (count($missingInDb) - 10) . " autres");
            }
            $this->newLine();
        }

        // Tester la synchronisation
        $this->info("🔄 Test de synchronisation...");
        $result = $syncService->syncFromText($content);
        
        $this->info("✅ Résultat:");
        $this->line("   • Créées: " . $result['created']);
        $this->line("   • Mises à jour: " . $result['updated']);
        $this->line("   • Marquées comme obsolètes: " . $result['marked_old']);
        $this->newLine();

        // Vérifier après sync
        $dbPermissionsAfter = Permission::where('is_old', false)->count();
        $this->info("📊 Permissions actives après sync: " . $dbPermissionsAfter);

        return 0;
    }
}
