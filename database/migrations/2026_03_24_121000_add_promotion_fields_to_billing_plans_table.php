<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('billing_plans')) {
            return;
        }

        Schema::table('billing_plans', function (Blueprint $table): void {
            if (!Schema::hasColumn('billing_plans', 'promo_type')) {
                $table->string('promo_type', 20)
                    ->nullable()
                    ->after('currency_code')
                    ->comment('percentage|fixed');
            }
            if (!Schema::hasColumn('billing_plans', 'promo_value')) {
                $table->decimal('promo_value', 12, 2)
                    ->nullable()
                    ->after('promo_type')
                    ->comment('Valeur promo (% ou montant fixe)');
            }
            if (!Schema::hasColumn('billing_plans', 'promo_starts_at')) {
                $table->timestamp('promo_starts_at')
                    ->nullable()
                    ->after('promo_value');
            }
            if (!Schema::hasColumn('billing_plans', 'promo_ends_at')) {
                $table->timestamp('promo_ends_at')
                    ->nullable()
                    ->after('promo_starts_at');
            }
            if (!Schema::hasColumn('billing_plans', 'promo_label')) {
                $table->string('promo_label', 120)
                    ->nullable()
                    ->after('promo_ends_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('billing_plans')) {
            return;
        }

        Schema::table('billing_plans', function (Blueprint $table): void {
            foreach (['promo_label', 'promo_ends_at', 'promo_starts_at', 'promo_value', 'promo_type'] as $column) {
                if (Schema::hasColumn('billing_plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
