<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('permissions')
            ->where('code', 'pharmacy.sales.wholesale')
            ->exists();

        if (!$exists) {
            DB::table('permissions')->insert([
                'code' => 'pharmacy.sales.wholesale',
                'description' => 'Vendre en mode gros (prix gros)',
                'group' => 'pharmacy',
                'is_old' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('code', 'pharmacy.sales.wholesale')
            ->delete();
    }
};
