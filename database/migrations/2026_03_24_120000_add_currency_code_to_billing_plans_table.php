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
            if (!Schema::hasColumn('billing_plans', 'currency_code')) {
                $table->string('currency_code', 3)
                    ->default('USD')
                    ->after('annual_price')
                    ->comment('Devise du plan (ISO 4217, ex: USD, EUR, CDF)');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('billing_plans')) {
            return;
        }

        Schema::table('billing_plans', function (Blueprint $table): void {
            if (Schema::hasColumn('billing_plans', 'currency_code')) {
                $table->dropColumn('currency_code');
            }
        });
    }
};
