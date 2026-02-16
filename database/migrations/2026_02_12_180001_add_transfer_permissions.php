<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            [
                'id' => Uuid::uuid4()->toString(),
                'code' => 'transfer.view',
                'name' => 'Voir les transferts',
                'description' => 'Permet de visualiser les transferts inter-magasins',
                'module' => 'pharmacy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'code' => 'transfer.create',
                'name' => 'Créer des transferts',
                'description' => 'Permet de créer de nouveaux transferts inter-magasins',
                'module' => 'pharmacy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'code' => 'transfer.validate',
                'name' => 'Valider les transferts',
                'description' => 'Permet de valider les transferts inter-magasins',
                'module' => 'pharmacy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'code' => 'transfer.cancel',
                'name' => 'Annuler les transferts',
                'description' => 'Permet d\'annuler les transferts inter-magasins',
                'module' => 'pharmacy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'code' => 'transfer.print',
                'name' => 'Imprimer les transferts',
                'description' => 'Permet d\'imprimer les bons de transfert',
                'module' => 'pharmacy',
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
