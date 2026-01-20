<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionSyncService;
use App\Services\RootRoleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixRootPermissions extends Command
{
    protected $signature = 'root:fix-permissions';
    protected $description = 'Crée les permissions et les assigne au ROOT user';

    public function handle(PermissionSyncService $permissionService, RootRoleService $roleService)
    {
        $this->info('🔧 Fixing ROOT permissions...');
        $this->newLine();

        // 1. Vérifier le ROOT user
        $rootUser = User::where('type', 'ROOT')->first();
        if (!$rootUser) {
            $this->error('ROOT user not found!');
            return 1;
        }
        $this->info('✅ ROOT user found: ' . $rootUser->email);

        // 2. Créer les permissions manuellement si elles n'existent pas
        $permissions = [
            'admin.tenants.select.view',
            'admin.tenants.dashboard.view',
            'admin.tenants.view',
            'admin.tenants.create',
            'admin.tenants.update',
            'admin.tenants.delete',
            'admin.users.view',
            'admin.users.create',
            'admin.users.update',
            'admin.users.delete',
        ];

        $this->info('📝 Creating permissions...');
        $permissionIds = [];
        foreach ($permissions as $code) {
            $permission = Permission::firstOrCreate(
                ['code' => $code],
                [
                    'group' => 'admin',
                    'description' => 'Permission: ' . $code,
                    'is_old' => false,
                ]
            );
            $permissionIds[] = $permission->id;
            $this->line("  ✓ {$code}");
        }

        $this->newLine();
        $this->info('✅ ' . count($permissionIds) . ' permissions created/verified');

        // 3. Créer ou récupérer le rôle ROOT
        $this->info('👤 Creating/verifying ROOT role...');
        $rootRole = $roleService->ensureRootRole();
        $this->info('✅ ROOT role: ' . $rootRole->name . ' (ID: ' . $rootRole->id . ')');

        // 4. Assigner toutes les permissions au rôle ROOT
        $this->info('🔗 Assigning permissions to ROOT role...');
        $rootRole->permissions()->sync($permissionIds);
        $this->info('✅ ' . count($permissionIds) . ' permissions assigned to ROOT role');

        // 5. Assigner le rôle ROOT à l'utilisateur
        $this->info('👥 Assigning ROOT role to user...');
        $rootUser->roles()->syncWithoutDetaching([
            $rootRole->id => ['tenant_id' => null],
        ]);
        $this->info('✅ ROOT role assigned to user');

        // 6. Vérification finale
        $this->newLine();
        $this->info('🔍 Verification:');
        $userPermissions = $rootUser->permissionCodes();
        $this->info('User has ' . count($userPermissions) . ' permissions:');
        foreach ($userPermissions as $perm) {
            $this->line("  ✓ {$perm}");
        }

        $this->newLine();
        $this->info('✅ All done! You can now login as ROOT.');
        
        return 0;
    }
}



