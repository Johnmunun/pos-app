<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DebugCategoryParsing extends Command
{
    protected $signature = 'permissions:debug-categories';
    protected $description = 'Déboguer le parsing des permissions de catégories';

    public function handle()
    {
        $content = Storage::disk('local')->get('permissions.yaml');
        $lines = preg_split('/\r\n|\r|\n/', $content);
        
        $this->info('🔍 Analyse du fichier YAML...');
        $this->info('📏 Taille du fichier: ' . strlen($content) . ' caractères');
        $this->info('📏 Nombre de lignes: ' . count($lines));
        $this->newLine();
        
        // Chercher "categories" dans le contenu brut
        if (str_contains($content, 'categories:')) {
            $this->info('✅ "categories:" trouvé dans le contenu brut');
            $pos = strpos($content, 'categories:');
            $this->info('   Position: ' . $pos);
            $this->info('   Contexte: ' . substr($content, max(0, $pos - 50), 100));
        } else {
            $this->error('❌ "categories:" NON trouvé dans le contenu brut!');
        }
        $this->newLine();
        
        $inCategories = false;
        $currentGroup = null;
        $foundCategories = [];
        
        foreach ($lines as $i => $rawLine) {
            $line = trim($rawLine);
            $lineNum = $i + 1;
            
            // Afficher les lignes autour de "categories"
            if ($lineNum >= 120 && $lineNum <= 135) {
                $this->line("Ligne {$lineNum}: '" . addslashes($rawLine) . "' (trimmed: '" . addslashes($line) . "')");
            }
            
            // Détecter le groupe categories
            if (str_ends_with($line, ':') && !str_starts_with($line, '-')) {
                $group = rtrim($line, ':');
                if ($group === 'categories') {
                    $inCategories = true;
                    $currentGroup = 'categories';
                    $this->info("✅ Ligne {$lineNum}: Groupe 'categories' détecté");
                    continue;
                } else {
                    $inCategories = false;
                    $currentGroup = $group;
                }
            }
            
            // Si on est dans la section categories
            if ($inCategories) {
                // Ignorer les lignes vides et commentaires
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                
                // Détecter les permissions
                if (str_starts_with($line, '-')) {
                    $code = trim(ltrim($line, '-'));
                    if (str_starts_with($code, 'categories.')) {
                        $foundCategories[] = $code;
                        $this->info("✅ Ligne {$lineNum}: Permission trouvée: {$code}");
                    } else {
                        $this->warn("⚠️  Ligne {$lineNum}: Format inattendu: {$line}");
                    }
                } else {
                    // Si on rencontre autre chose, on sort de la section
                    if (!str_ends_with($line, ':')) {
                        $this->line("ℹ️  Ligne {$lineNum}: Fin de section categories (ligne: '{$line}')");
                        $inCategories = false;
                    }
                }
            }
        }
        
        $this->newLine();
        $this->info("📊 Résultat: " . count($foundCategories) . " permissions de catégories trouvées:");
        foreach ($foundCategories as $code) {
            $this->line("   • {$code}");
        }
        
        return 0;
    }
}

