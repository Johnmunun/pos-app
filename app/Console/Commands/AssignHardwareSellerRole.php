<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignHardwareSellerRole extends Command
{
    protected $signature = 'hardware:assign-seller-role 
                            {email : Email de l\'utilisateur}
                            {--tenant-id= : ID du tenant (optionnel)}';

    protected $description = 'Assigner le rôle "Vendeur Hardware" à un utilisateur';

    public function handle(): int
    {
        $email = $this->argument('email');
        $tenantId = $this->option('tenant-id');

        // 1. Vérifier que le rôle existe
        $role = Role::where('name', 'Vendeur Hardware')
            ->whereNull('tenant_id')
            ->first();

        if (!$role) {
            $this->error('❌ Le rôle "Vendeur Hardware" n\'existe pas.');
            $this->info('💡 Exécutez d\'abord: php artisan db:seed --class=DefaultSectorRolesSeeder');
            return self::FAILURE;
        }

        $this->info("✅ Rôle trouvé: {$role->name} (ID: {$role->id})");

        // Vérifier les permissions du rôle
        $permissions = $role->permissions->pluck('code')->toArray();
        $this->info("   Permissions: " . count($permissions));
        
        if (!in_array('module.hardware', $permissions, true)) {
            $this->error('❌ Le rôle n\'a pas la permission "module.hardware"');
            return self::FAILURE;
        }
        $this->info("   ✅ Permission 'module.hardware' présente");

        // 2. Trouver l'utilisateur
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("❌ Utilisateur non trouvé: {$email}");
            return self::FAILURE;
        }

        $this->info("✅ Utilisateur trouvé: {$user->name} (ID: {$user->id})");

        // Utiliser le tenant_id de l'utilisateur si non fourni
        if (!$tenantId) {
            $tenantId = $user->tenant_id;
        }

        if (!$tenantId) {
            $this->error('❌ L\'utilisateur n\'a pas de tenant_id. Fournissez --tenant-id');
            return self::FAILURE;
        }

        $this->info("   Tenant ID: {$tenantId}");

        // 3. Vérifier si le rôle est déjà assigné
        $alreadyAssigned = DB::table('user_role')
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->where('tenant_id', $tenantId)
            ->exists();

        if ($alreadyAssigned) {
            $this->warn("⚠️  Le rôle est déjà assigné à cet utilisateur pour ce tenant.");
            $this->info('💡 Déconnectez-vous et reconnectez-vous pour voir les changements.');
            return self::SUCCESS;
        }

        // 4. Assigner le rôle
        try {
            DB::table('user_role')->insert([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'tenant_id' => $tenantId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->info("✅ Rôle assigné avec succès!");

            // 5. Vérifier les permissions
            $user->refresh();
            $userPermissions = $user->permissionCodes();

            if (in_array('module.hardware', $userPermissions, true)) {
                $this->info("✅ Permission 'module.hardware' confirmée dans les permissions de l'utilisateur");
            } else {
                $this->warn("⚠️  La permission 'module.hardware' n'apparaît pas encore.");
                $this->info('💡 Déconnectez-vous et reconnectez-vous pour recharger les permissions.');
            }

            $this->newLine();
            $this->info('📋 Prochaines étapes:');
            $this->info('   1. Déconnectez-vous complètement');
            $this->info('   2. Reconnectez-vous');
            $this->info('   3. Le module "Quincaillerie" devrait apparaître dans la sidebar');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de l'assignation: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
