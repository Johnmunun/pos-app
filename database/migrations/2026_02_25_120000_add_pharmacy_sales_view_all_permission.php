<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('permissions')->where('code', 'pharmacy.sales.view.all')->first();
        if (!$exists) {
            DB::table('permissions')->insert([
                'code' => 'pharmacy.sales.view.all',
                'description' => 'Voir toutes les ventes (généralité). Sans cette permission, le vendeur ne voit que ses propres ventes.',
                'group' => 'pharmacy',
                'is_old' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('code', 'pharmacy.sales.view.all')->delete();
    }
};
