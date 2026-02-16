<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permissions pour le module Fournisseurs Pharmacy.
     */
    private array $permissions = [
        [
            'code' => 'pharmacy.supplier.view',
            'description' => 'Voir la liste des fournisseurs',
            'group' => 'pharmacy',
        ],
        [
            'code' => 'pharmacy.supplier.create',
            'description' => 'Créer un nouveau fournisseur',
            'group' => 'pharmacy',
        ],
        [
            'code' => 'pharmacy.supplier.edit',
            'description' => 'Modifier un fournisseur',
            'group' => 'pharmacy',
        ],
        [
            'code' => 'pharmacy.supplier.activate',
            'description' => 'Activer un fournisseur',
            'group' => 'pharmacy',
        ],
        [
            'code' => 'pharmacy.supplier.deactivate',
            'description' => 'Désactiver un fournisseur',
            'group' => 'pharmacy',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->permissions as $permission) {
            // Vérifier si la permission existe déjà
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $codes = array_column($this->permissions, 'code');
        DB::table('permissions')->whereIn('code', $codes)->delete();
    }
};
