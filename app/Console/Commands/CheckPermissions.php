<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Services\PermissionSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CheckPermissions extends Command
{
    protected $signature = 'permissions:check';
    protected $description = 'Vérifier la cohérence entre les permissions du fichier YAML et la base de données';

    public function handle()
    {
        $this->info('🔍 Vérification des permissions...');
        $this->newLine();

        // Lire le fichier YAML
        $yamlPath = 'permissions.yaml';
        if (!Storage::disk('local')->exists($yamlPath)) {
            $this->error("❌ Le fichier {$yamlPath} n'existe pas dans storage/app/");
            return 1;
        }

        $content = Storage::disk('local')->get($yamlPath);
        $syncService = new PermissionSyncService();
        $yamlPermissions = $syncService->syncFromText($content);

        // Parser les permissions du fichier
        $yamlCodes = $this->parseYamlPermissions($content);

        // Récupérer les permissions de la base de données
        $dbPermissions = Permission::where('is_old', false)
            ->orderBy('code')
            ->get();

        $dbCodes = $dbPermissions->pluck('code')->toArray();

        // Comparaison
        $inYamlNotInDb = array_diff($yamlCodes, $dbCodes);
        $inDbNotInYaml = array_diff($dbCodes, $yamlCodes);
        $inBoth = array_intersect($yamlCodes, $dbCodes);

        // Affichage des résultats
        $this->info("📊 Statistiques:");
        $this->line("   • Permissions dans le fichier YAML: " . count($yamlCodes));
        $this->line("   • Permissions dans la base de données: " . count($dbCodes));
        $this->line("   • Permissions communes: " . count($inBoth));
        $this->newLine();

        // Permissions dans YAML mais pas dans DB
        if (!empty($inYamlNotInDb)) {
            $this->warn("⚠️  Permissions dans le fichier YAML mais ABSENTES de la base de données (" . count($inYamlNotInDb) . "):");
            foreach ($inYamlNotInDb as $code) {
                $this->line("   • {$code}");
            }
            $this->newLine();
        } else {
            $this->info("✅ Toutes les permissions du fichier YAML sont présentes dans la base de données.");
            $this->newLine();
        }

        // Permissions dans DB mais pas dans YAML
        if (!empty($inDbNotInYaml)) {
            $this->warn("⚠️  Permissions dans la base de données mais ABSENTES du fichier YAML (" . count($inDbNotInYaml) . "):");
            foreach ($inDbNotInYaml as $code) {
                $permission = $dbPermissions->firstWhere('code', $code);
                $this->line("   • {$code} (groupe: {$permission->group ?? 'N/A'})");
            }
            $this->newLine();
        } else {
            $this->info("✅ Toutes les permissions de la base de données sont présentes dans le fichier YAML.");
            $this->newLine();
        }

        // Détails des permissions dans la DB
        $this->info("📋 Détails des permissions dans la base de données:");
        $this->table(
            ['Code', 'Groupe', 'Description', 'Créée le'],
            $dbPermissions->map(function ($perm) {
                return [
                    $perm->code,
                    $perm->group ?? 'N/A',
                    $perm->description ?? 'N/A',
                    $perm->created_at->format('Y-m-d H:i:s'),
                ];
            })->toArray()
        );

        // Résumé
        $this->newLine();
        if (empty($inYamlNotInDb) && empty($inDbNotInYaml)) {
            $this->info("✅ Parfait ! Les permissions sont synchronisées.");
        } else {
            $this->warn("⚠️  Il y a des différences. Exécutez 'php artisan permissions:sync' pour synchroniser.");
        }

        return 0;
    }

    /**
     * Parser le fichier YAML pour extraire les codes de permissions
     */
    private function parseYamlPermissions(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $permissions = [];
        $currentGroup = null;

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);

            // Ignorer les lignes vides et les commentaires
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Détecter un groupe (ligne qui se termine par ':' sans être une permission)
            if (str_ends_with($line, ':') && !str_starts_with($line, '-')) {
                $currentGroup = rtrim($line, ':');
                continue;
            }

            // Extraire le code de permission
            $code = null;
            
            // Format YAML avec tiret: "- permission.code"
            if (str_starts_with($line, '-')) {
                $code = trim(ltrim($line, '-'));
                // Nettoyer les commentaires inline
                if (str_contains($code, '#')) {
                    $code = trim(explode('#', $code)[0]);
                }
            } 
            // Format simple: "permission.code"
            else {
                $code = $line;
                // Nettoyer les commentaires inline
                if (str_contains($code, '#')) {
                    $code = trim(explode('#', $code)[0]);
                }
            }

            // Valider et ajouter la permission
            if ($code && $code !== '' && !str_ends_with($code, ':')) {
                $permissions[] = $code;
            }
        }

        return array_unique($permissions);
    }
}


