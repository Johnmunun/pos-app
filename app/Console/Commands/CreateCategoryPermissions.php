<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;

class CreateCategoryPermissions extends Command
{
    protected $signature = 'permissions:create-categories';
    protected $description = 'Créer les permissions de catégories si elles n\'existent pas';

    public function handle()
    {
        $this->info('🔍 Vérification des permissions de catégories...');
        $this->newLine();

        $permissions = [
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',
        ];

        $created = 0;
        $updated = 0;

        foreach ($permissions as $code) {
            $permission = Permission::where('code', $code)->first();

            if (!$permission) {
                Permission::create([
                    'code' => $code,
                    'group' => 'categories',
                    'is_old' => false,
                ]);
                $this->info("✅ Créée: {$code}");
                $created++;
            } else {
                if ($permission->is_old) {
                    $permission->update(['is_old' => false]);
                    $this->info("✅ Réactivée: {$code}");
                    $updated++;
                } else {
                    $this->line("ℹ️  Déjà existante: {$code}");
                }
            }
        }

        $this->newLine();
        $this->info("📊 Résultat: {$created} créée(s), {$updated} réactivée(s)");

        // Assigner au rôle ROOT
        $rootRole = \App\Models\Role::where('name', 'ROOT')->whereNull('tenant_id')->first();
        if ($rootRole) {
            $permissionIds = Permission::whereIn('code', $permissions)
                ->where('is_old', false)
                ->pluck('id')
                ->all();
            
            if (!empty($permissionIds)) {
                $rootRole->permissions()->syncWithoutDetaching($permissionIds);
                $this->info("✅ Permissions assignées au rôle ROOT");
            }
        }

        return 0;
    }
}

