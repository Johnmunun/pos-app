<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'store_start_mode')) {
                $table->string('store_start_mode', 32)
                    ->nullable()
                    ->after('sector')
                    ->comment('empty_store | preconfigured_store');
            }
            if (!Schema::hasColumn('tenants', 'is_store_initialized')) {
                $table->boolean('is_store_initialized')
                    ->default(false)
                    ->after('store_start_mode')
                    ->index()
                    ->comment('Provisioning idempotent terminé pour cette boutique');
            }
            if (!Schema::hasColumn('tenants', 'template_code')) {
                $table->string('template_code', 64)
                    ->nullable()
                    ->after('is_store_initialized')
                    ->comment('Code du pack appliqué (ex: pharmacy_preconfigured_v1)');
            }
            if (!Schema::hasColumn('tenants', 'template_applied_at')) {
                $table->timestamp('template_applied_at')
                    ->nullable()
                    ->after('template_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            foreach (['store_start_mode', 'is_store_initialized', 'template_code', 'template_applied_at'] as $col) {
                if (Schema::hasColumn('tenants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
