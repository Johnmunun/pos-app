<?php

namespace App\Services;

use App\Models\Permission;
use Illuminate\Support\Facades\Storage;

class PermissionSyncService
{
    /**
     * Synchroniser les permissions depuis le fichier par défaut.
     */
    public function syncFromDefaultFile(string $path = 'permissions.yaml'): array
    {
        if (!Storage::disk('local')->exists($path)) {
            return [
                'created' => 0,
                'updated' => 0,
                'marked_old' => 0,
            ];
        }

        $content = Storage::disk('local')->get($path);

        return $this->syncFromText($content);
    }

    /**
     * Synchroniser les permissions depuis un texte YAML/TXT.
     */
    public function syncFromText(string $text): array
    {
        $permissions = $this->parsePermissions($text);

        if (empty($permissions)) {
            return [
                'created' => 0,
                'updated' => 0,
                'marked_old' => 0,
            ];
        }

        $created = 0;
        $updated = 0;
        $codes = array_keys($permissions);

        foreach ($permissions as $code => $group) {
            $permission = Permission::where('code', $code)->first();

            if (!$permission) {
                Permission::create([
                    'code' => $code,
                    'group' => $group,
                    'is_old' => false,
                ]);
                $created++;
                continue;
            }

            $changes = [];
            if ($permission->is_old) {
                $changes['is_old'] = false;
            }
            if ($group && $permission->group !== $group) {
                $changes['group'] = $group;
            }

            if (!empty($changes)) {
                $permission->update($changes);
                $updated++;
            }
        }

        $markedOld = Permission::whereNotIn('code', $codes)
            ->where('is_old', false)
            ->update(['is_old' => true]);

        return [
            'created' => $created,
            'updated' => $updated,
            'marked_old' => $markedOld,
        ];
    }

    /**
     * Parser un texte YAML/TXT en permissions.
     *
     * @return array<string, string|null> code => group
     */
    private function parsePermissions(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text);
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
                $permissions[$code] = $currentGroup;
            }
        }

        return $permissions;
    }
}

