<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User as UserModel;
use App\Services\PermissionSyncService;
use App\Services\RootRoleService;

/**
 * Seeder: CreateRootUserSeeder
 *
 * Crée l'utilisateur ROOT initial.
 *
 * ⚠️ À exécuter UNE SEULE FOIS lors de la mise en place initiale.
 * Après cela, aucun autre utilisateur ROOT ne peut être créé (via la sécurité du domain).
 *
 * Commande:
 * php artisan db:seed --class=CreateRootUserSeeder
 */
class CreateRootUserSeeder extends Seeder
{
    public function run(): void
    {
        // Vérifier qu'aucun ROOT n'existe déjà
        $rootUser = UserModel::where('type', 'ROOT')->first();

        if (!$rootUser) {
            // Créer le ROOT user
            // ⚠️ EN PRODUCTION: Utiliser des variables d'environnement ou des secrets
            $rootUser = UserModel::create([
                'name' => 'Admin Root', // Requis par la table users
                'email' => 'root@pos-saas.local',
                'password' => bcrypt('RootPassword123'),  // Changer en production!
                'first_name' => 'Admin',
                'last_name' => 'Root',
                'type' => 'ROOT',
                'tenant_id' => null,
                'is_active' => true,
            ]);

            $this->command->info('ROOT user created successfully!');
            $this->command->warn('⚠️  Change password immediately in production!');
        } else {
            $this->command->info('ROOT user already exists. Updating permissions.');
        }

        // Synchroniser les permissions depuis le fichier source
        $syncService = app(PermissionSyncService::class);
        $syncResult = $syncService->syncFromDefaultFile();

        $this->command->info(sprintf(
            'Permissions synced. Created: %d, Updated: %d, Marked old: %d',
            $syncResult['created'],
            $syncResult['updated'],
            $syncResult['marked_old']
        ));

        // Assigner le rôle ROOT + permissions
        $roleService = app(RootRoleService::class);
        $rootRole = $roleService->ensureRootRole();
        $roleService->syncRolePermissions($rootRole);
        $roleService->assignRoleToUser($rootUser, $rootRole);
    }
}
