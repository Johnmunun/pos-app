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
        $permission = [
            'code' => 'pharmacy.report.view',
            'description' => 'Voir les rapports Pharmacy',
            'group' => 'pharmacy',
            'is_old' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $exists = DB::table('permissions')
            ->where('code', $permission['code'])
            ->exists();

        if (!$exists) {
            DB::table('permissions')->insert($permission);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')
            ->where('code', 'pharmacy.report.view')
            ->delete();
    }
};
