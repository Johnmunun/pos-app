<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;

class CreateMissingPermissions extends Command
{
    protected $signature = 'permissions:create-missing';
    protected $description = 'Créer les permissions manquantes depuis le YAML';

    public function handle()
    {
        $this->info('🔍 Création des permissions manquantes...');
        $this->newLine();

        // Permissions manquantes identifiées
        $missingPermissions = [
            // Générales
            ['code' => 'dashboard.view', 'group' => 'general'],
            ['code' => 'notifications.view', 'group' => 'general'],
            ['code' => 'activity.view', 'group' => 'general'],
            
            // Commerce
            ['code' => 'sales.view', 'group' => 'commerce'],
            ['code' => 'sales.create', 'group' => 'commerce'],
            ['code' => 'invoices.view', 'group' => 'commerce'],
            ['code' => 'customers.view', 'group' => 'commerce'],
            ['code' => 'sellers.view', 'group' => 'commerce'],
            ['code' => 'sellers.create', 'group' => 'commerce'],
            
            // Produits
            ['code' => 'products.view', 'group' => 'products'],
            ['code' => 'products.create', 'group' => 'products'],
            ['code' => 'inventory.view', 'group' => 'products'],
            
            // Paiements
            ['code' => 'payments.view', 'group' => 'payments'],
            ['code' => 'payments.methods', 'group' => 'payments'],
            ['code' => 'finance.reports', 'group' => 'payments'],
            
            // Support
            ['code' => 'support.tickets.create', 'group' => 'support'],
            ['code' => 'support.tickets.view', 'group' => 'support'],
            ['code' => 'support.admin', 'group' => 'support'],
            ['code' => 'support.faq', 'group' => 'support'],
            
            // Rapports
            ['code' => 'reports.view', 'group' => 'reports'],
            ['code' => 'reports.export', 'group' => 'reports'],
            ['code' => 'analytics.view', 'group' => 'reports'],
            
            // Logs
            ['code' => 'logs.system', 'group' => 'logs'],
            ['code' => 'logs.actions', 'group' => 'logs'],
            ['code' => 'logs.connections', 'group' => 'logs'],
            
            // Settings
            ['code' => 'settings.view', 'group' => 'settings'],
            ['code' => 'settings.branding', 'group' => 'settings'],
            ['code' => 'settings.ui', 'group' => 'settings'],
            
            // Modules (réactiver celles marquées comme obsolètes)
            ['code' => 'module.pharmacy', 'group' => 'modules'],
            ['code' => 'module.butchery', 'group' => 'modules'],
            ['code' => 'module.kiosk', 'group' => 'modules'],
            ['code' => 'module.supermarket', 'group' => 'modules'],
            ['code' => 'module.hardware', 'group' => 'modules'],
        ];

        $created = 0;
        $reactivated = 0;

        foreach ($missingPermissions as $perm) {
            $permission = Permission::where('code', $perm['code'])->first();

            if (!$permission) {
                Permission::create([
                    'code' => $perm['code'],
                    'group' => $perm['group'],
                    'is_old' => false,
                ]);
                $this->info("✅ Créée: {$perm['code']}");
                $created++;
            } else {
                if ($permission->is_old) {
                    $permission->update([
                        'is_old' => false,
                        'group' => $perm['group'],
                    ]);
                    $this->info("✅ Réactivée: {$perm['code']}");
                    $reactivated++;
                } else {
                    // Mettre à jour le groupe si différent
                    if ($permission->group !== $perm['group']) {
                        $permission->update(['group' => $perm['group']]);
                        $this->line("ℹ️  Groupe mis à jour: {$perm['code']} ({$perm['group']})");
                    }
                }
            }
        }

        $this->newLine();
        $this->info("📊 Résultat: {$created} créée(s), {$reactivated} réactivée(s)");

        // Assigner au rôle ROOT
        $rootRole = \App\Models\Role::where('name', 'ROOT')->whereNull('tenant_id')->first();
        if ($rootRole) {
            $permissionIds = Permission::whereIn('code', array_column($missingPermissions, 'code'))
                ->where('is_old', false)
                ->pluck('id')
                ->all();
            
            if (!empty($permissionIds)) {
                $rootRole->permissions()->syncWithoutDetaching($permissionIds);
                $this->info("✅ Permissions assignées au rôle ROOT");
            }
        }

        $this->newLine();
        $this->info("📊 Total permissions actives: " . Permission::where('is_old', false)->count());

        return 0;
    }
}

