<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Crée les rôles "système" (tenant_id = null) que les propriétaires
 * peuvent assigner à leurs vendeurs sans avoir à créer de rôles.
 * ROOT exécute ce seeder une fois ; les rôles apparaissent pour tous les tenants du secteur.
 */
class DefaultSectorRolesSeeder extends Seeder
{
    private array $createdRoles = [];

    public function run(): void
    {
        $this->createVendeurPharmacyRole();
        $this->createVendeurHardwareRole();
        $this->createVendeurCommerceRole();
        
        // Générer le fichier de documentation
        $this->generateMarkdownDocumentation();
    }

    private function createVendeurPharmacyRole(): void
    {
        $name = 'Vendeur Pharmacie';
        $existingRole = Role::where('name', $name)->whereNull('tenant_id')->first();
        if ($existingRole) {
            if ($this->command) {
                $this->command->info("Rôle \"{$name}\" (global) existe déjà.");
            }
            // Enregistrer quand même pour la documentation
            $permissions = $existingRole->permissions->pluck('code')->toArray();
            $this->createdRoles[] = [
                'name' => $name,
                'description' => $existingRole->description,
                'permissions' => $permissions,
                'permissions_count' => count($permissions),
                'created' => false,
            ];
            return;
        }

        $role = Role::create([
            'tenant_id' => null,
            'name' => $name,
            'description' => 'Rôle par défaut pour les vendeurs en pharmacie (créé par le système). Assignable par le propriétaire.',
            'is_active' => true,
        ]);

        $codes = [
            'module.pharmacy',
            'pharmacy.product.view',
            'pharmacy.sales.view',
            'pharmacy.sales.manage',
            'stock.view',
            'pharmacy.customer.view',
        ];

        /** @var array<int, int> $permissionIds */
        $permissionIds = Permission::where('is_old', false)
            ->whereIn('code', $codes)
            ->pluck('id')
            ->toArray();

        if ($permissionIds !== []) {
            $role->permissions()->sync($permissionIds);
        }

        // Enregistrer les informations du rôle pour la documentation
        $this->createdRoles[] = [
            'name' => $name,
            'description' => $role->description,
            'permissions' => $codes,
            'permissions_count' => count($permissionIds),
            'created' => true,
        ];

        if ($this->command) {
            $this->command->info("Rôle \"{$name}\" créé avec " . count($permissionIds) . " permission(s).");
        }
    }

    private function createVendeurHardwareRole(): void
    {
        $name = 'Vendeur Hardware';
        $existingRole = Role::where('name', $name)->whereNull('tenant_id')->first();
        if ($existingRole) {
            if ($this->command) {
                $this->command->info("Rôle \"{$name}\" (global) existe déjà.");
            }
            // Enregistrer quand même pour la documentation
            $permissions = $existingRole->permissions->pluck('code')->toArray();
            $this->createdRoles[] = [
                'name' => $name,
                'description' => $existingRole->description,
                'permissions' => $permissions,
                'permissions_count' => count($permissions),
                'created' => false,
            ];
            return;
        }

        $role = Role::create([
            'tenant_id' => null,
            'name' => $name,
            'description' => 'Rôle par défaut pour les vendeurs en quincaillerie (créé par le système). Assignable par le propriétaire.',
            'is_active' => true,
        ]);

        $codes = [
            'module.hardware', // Permission essentielle pour voir le module dans la sidebar
            'hardware.product.view',
            'hardware.sales.view',
            'hardware.sales.manage',
            'hardware.stock.view',
            'hardware.customer.view',
        ];

        /** @var array<int, int> $permissionIds */
        $permissionIds = Permission::where('is_old', false)
            ->whereIn('code', $codes)
            ->pluck('id')
            ->toArray();

        if ($permissionIds !== []) {
            $role->permissions()->sync($permissionIds);
        }

        // Enregistrer les informations du rôle pour la documentation
        $this->createdRoles[] = [
            'name' => $name,
            'description' => $role->description,
            'permissions' => $codes,
            'permissions_count' => count($permissionIds),
            'created' => true,
        ];

        if ($this->command) {
            $this->command->info("Rôle \"{$name}\" créé avec " . count($permissionIds) . " permission(s).");
        }
    }

    private function createVendeurCommerceRole(): void
    {
        $name = 'Vendeur Commerce';
        $existingRole = Role::where('name', $name)->whereNull('tenant_id')->first();
        if ($existingRole) {
            if ($this->command) {
                $this->command->info("Rôle \"{$name}\" (global) existe déjà.");
            }
            // Enregistrer quand même pour la documentation
            $permissions = $existingRole->permissions->pluck('code')->toArray();
            $this->createdRoles[] = [
                'name' => $name,
                'description' => $existingRole->description,
                'permissions' => $permissions,
                'permissions_count' => count($permissions),
                'created' => false,
            ];
            return;
        }

        $role = Role::create([
            'tenant_id' => null,
            'name' => $name,
            'description' => 'Rôle par défaut pour les vendeurs en commerce (créé par le système). Assignable par le propriétaire.',
            'is_active' => true,
        ]);

        $codes = [
            'module.commerce', // Permission essentielle pour voir le module dans la sidebar
            'commerce.product.view',
            'sales.view', // Permissions générales pour les ventes
            'sales.create',
            'commerce.stock.view',
            'commerce.customer.view',
        ];

        /** @var array<int, int> $permissionIds */
        $permissionIds = Permission::where('is_old', false)
            ->whereIn('code', $codes)
            ->pluck('id')
            ->toArray();

        if ($permissionIds !== []) {
            $role->permissions()->sync($permissionIds);
        }

        // Enregistrer les informations du rôle pour la documentation
        $this->createdRoles[] = [
            'name' => $name,
            'description' => $role->description,
            'permissions' => $codes,
            'permissions_count' => count($permissionIds),
            'created' => true,
        ];

        if ($this->command) {
            $this->command->info("Rôle \"{$name}\" créé avec " . count($permissionIds) . " permission(s).");
        }
    }

    /**
     * Génère un fichier Markdown documentant les rôles créés
     */
    private function generateMarkdownDocumentation(): void
    {
        $docsPath = base_path('docs');
        if (!File::exists($docsPath)) {
            File::makeDirectory($docsPath, 0755, true);
        }

        $content = "# Rôles Système par Défaut\n\n";
        $content .= "> **Généré automatiquement** par `DefaultSectorRolesSeeder`\n";
        $content .= "> **Date:** " . now()->format('Y-m-d H:i:s') . "\n\n";
        $content .= "Ce document liste tous les rôles système (globaux) créés automatiquement par le seeder.\n";
        $content .= "Ces rôles peuvent être assignés aux vendeurs depuis le module Commerce.\n\n";
        $content .= "---\n\n";

        foreach ($this->createdRoles as $roleData) {
            $status = $roleData['created'] ? '✅ Créé' : 'ℹ️ Existant';
            $content .= "## {$roleData['name']} {$status}\n\n";
            $content .= "**Description:** {$roleData['description']}\n\n";
            $content .= "**Nombre de permissions:** {$roleData['permissions_count']}\n\n";
            $content .= "### Permissions assignées:\n\n";
            
            foreach ($roleData['permissions'] as $permission) {
                $content .= "- `{$permission}`\n";
            }
            
            $content .= "\n---\n\n";
        }

        $content .= "## Utilisation\n\n";
        $content .= "1. Ces rôles sont créés automatiquement lors de l'exécution du seeder\n";
        $content .= "2. Ils sont disponibles pour tous les tenants (rôles globaux, `tenant_id = null`)\n";
        $content .= "3. Les propriétaires peuvent les assigner à leurs vendeurs depuis `/commerce/sellers`\n";
        $content .= "4. Les rôles sont filtrés automatiquement selon le secteur du tenant\n\n";
        $content .= "## Commande pour exécuter le seeder\n\n";
        $content .= "```bash\n";
        $content .= "php artisan db:seed --class=DefaultSectorRolesSeeder\n";
        $content .= "```\n";

        $filePath = $docsPath . '/DEFAULT_SELLER_ROLES.md';
        File::put($filePath, $content);

        if ($this->command) {
            $this->command->info("📄 Documentation générée: {$filePath}");
        }
    }
}
