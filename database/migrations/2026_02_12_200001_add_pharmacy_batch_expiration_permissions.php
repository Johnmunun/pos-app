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
            // Batch permissions
            [
                'code' => 'pharmacy.batch.view',
                'description' => 'Voir les lots',
                'group' => 'pharmacy',
                'is_old' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'pharmacy.batch.manage',
                'description' => 'Gérer les lots',
                'group' => 'pharmacy',
                'is_old' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Expiration permissions
            [
                'code' => 'pharmacy.expiration.view',
                'description' => 'Voir les expirations',
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
        DB::table('permissions')
            ->whereIn('code', [
                'pharmacy.batch.view',
                'pharmacy.batch.manage',
                'pharmacy.expiration.view',
            ])
            ->delete();
    }
};
