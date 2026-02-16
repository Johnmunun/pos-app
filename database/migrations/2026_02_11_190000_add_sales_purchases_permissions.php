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
                'code' => 'pharmacy.sales.view',
                'description' => 'Voir les ventes',
                'group' => 'pharmacy',
                'is_old' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'pharmacy.sales.manage',
                'description' => 'Créer et gérer les ventes (caisse)',
                'group' => 'pharmacy',
                'is_old' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'pharmacy.sales.cancel',
                'description' => 'Annuler une vente (brouillon)',
                'group' => 'pharmacy',
                'is_old' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'pharmacy.purchases.view',
                'description' => 'Voir les bons de commande',
                'group' => 'pharmacy',
                'is_old' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'pharmacy.purchases.manage',
                'description' => 'Créer et gérer les bons de commande',
                'group' => 'pharmacy',
                'is_old' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'pharmacy.purchases.receive',
                'description' => 'Enregistrer la réception de marchandises',
                'group' => 'pharmacy',
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
            ->whereIn('code', [
                'pharmacy.sales.view',
                'pharmacy.sales.manage',
                'pharmacy.sales.cancel',
                'pharmacy.purchases.view',
                'pharmacy.purchases.manage',
                'pharmacy.purchases.receive',
            ])
            ->delete();
    }
};
