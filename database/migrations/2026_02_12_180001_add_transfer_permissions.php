<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            [
                'code' => 'transfer.view',
                'description' => 'Voir les transferts - Permet de visualiser les transferts inter-magasins',
                'group' => 'pharmacy',
                'is_old' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'transfer.create',
                'description' => 'Créer des transferts - Permet de créer de nouveaux transferts inter-magasins',
                'group' => 'pharmacy',
                'is_old' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'transfer.validate',
                'description' => 'Valider les transferts - Permet de valider les transferts inter-magasins',
                'group' => 'pharmacy',
                'is_old' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'transfer.cancel',
                'description' => 'Annuler les transferts - Permet d\'annuler les transferts inter-magasins',
                'group' => 'pharmacy',
                'is_old' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'transfer.print',
                'description' => 'Imprimer les transferts - Permet d\'imprimer les bons de transfert',
                'group' => 'pharmacy',
                'is_old' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($permissions as $permission) {
            // Vérifier si la permission existe déjà
            $exists = DB::table('permissions')
                ->where('code', $permission['code'])
                ->exists();

            if (!$exists) {
                DB::table('permissions')->insert($permission);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $codes = [
            'transfer.view',
            'transfer.create',
            'transfer.validate',
            'transfer.cancel',
            'transfer.print',
        ];

        DB::table('permissions')->whereIn('code', $codes)->delete();
    }
};
