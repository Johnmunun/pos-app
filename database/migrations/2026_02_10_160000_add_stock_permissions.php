<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $permissions = [
            [
                'code' => 'stock.view',
                'description' => 'Voir la page de gestion de stock',
                'group' => 'stock',
                'is_old' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'stock.adjust',
                'description' => 'Ajuster le stock des produits',
                'group' => 'stock',
                'is_old' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'stock.movement.view',
                'description' => 'Voir l’historique des mouvements de stock',
                'group' => 'stock',
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

    public function down(): void
    {
        DB::table('permissions')
            ->whereIn('code', ['stock.view', 'stock.adjust', 'stock.movement.view'])
            ->delete();
    }
};

