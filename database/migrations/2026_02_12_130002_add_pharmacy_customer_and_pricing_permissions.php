<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        // Permissions Prix Fournisseur
        ['code' => 'pharmacy.supplier.pricing.view', 'description' => 'Voir les prix fournisseur', 'group' => 'pharmacy'],
        ['code' => 'pharmacy.supplier.pricing.manage', 'description' => 'Gérer les prix fournisseur', 'group' => 'pharmacy'],
        
        // Permissions Clients
        ['code' => 'pharmacy.customer.view', 'description' => 'Voir la liste des clients', 'group' => 'pharmacy'],
        ['code' => 'pharmacy.customer.create', 'description' => 'Créer un nouveau client', 'group' => 'pharmacy'],
        ['code' => 'pharmacy.customer.edit', 'description' => 'Modifier un client', 'group' => 'pharmacy'],
        ['code' => 'pharmacy.customer.activate', 'description' => 'Activer un client', 'group' => 'pharmacy'],
        ['code' => 'pharmacy.customer.deactivate', 'description' => 'Désactiver un client', 'group' => 'pharmacy'],
    ];

    public function up(): void
    {
        foreach ($this->permissions as $permission) {
            $exists = DB::table('permissions')
                ->where('code', $permission['code'])
                ->exists();

            if (!$exists) {
                DB::table('permissions')->insert([
                    'code' => $permission['code'],
                    'description' => $permission['description'],
                    'group' => $permission['group'],
                    'is_old' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $codes = array_column($this->permissions, 'code');
        DB::table('permissions')->whereIn('code', $codes)->delete();
    }
};
