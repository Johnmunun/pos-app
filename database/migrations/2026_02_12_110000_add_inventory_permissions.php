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
        $now = now();
        $permissions = [
            [
                'code' => 'inventory.view',
                'description' => 'Voir les inventaires',
                'group' => 'inventory',
                'is_old' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'inventory.create',
                'description' => 'Créer un inventaire',
                'group' => 'inventory',
                'is_old' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'inventory.edit',
                'description' => 'Modifier un inventaire (saisie des quantités)',
                'group' => 'inventory',
                'is_old' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'inventory.validate',
                'description' => 'Valider un inventaire (applique les ajustements)',
                'group' => 'inventory',
                'is_old' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'inventory.cancel',
                'description' => 'Annuler un inventaire',
                'group' => 'inventory',
                'is_old' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($permissions as $perm) {
            $exists = DB::table('permissions')->where('code', $perm['code'])->first();
            if (!$exists) {
                DB::table('permissions')->insert($perm);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')
            ->whereIn('code', [
                'inventory.view',
                'inventory.create',
                'inventory.edit',
                'inventory.validate',
                'inventory.cancel',
            ])
            ->delete();
    }
};
