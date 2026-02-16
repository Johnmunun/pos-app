<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permission = DB::table('permissions')->where('code', 'pharmacy.product.import')->first();
        if (!$permission) {
            DB::table('permissions')->insert([
                'code' => 'pharmacy.product.import',
                'description' => 'Importer des produits (fichier Excel/CSV)',
                'group' => 'pharmacy',
                'is_old' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('code', 'pharmacy.product.import')->delete();
    }
};
