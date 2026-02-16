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
                'name' => 'pharmacy.batch.view',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'pharmacy.batch.manage',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Expiration permissions
            [
                'name' => 'pharmacy.expiration.view',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($permissions as $permission) {
            // Check if permission already exists
            $exists = DB::table('permissions')
                ->where('name', $permission['name'])
                ->where('guard_name', $permission['guard_name'])
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
            ->whereIn('name', [
                'pharmacy.batch.view',
                'pharmacy.batch.manage',
                'pharmacy.expiration.view',
            ])
            ->delete();
    }
};
