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
                'code' => 'stock.movement.print',
                'description' => 'Imprimer mouvements stock - Permet d\'imprimer les fiches de mouvements de stock',
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
            ->where('code', 'stock.movement.print')
            ->delete();
    }
};
