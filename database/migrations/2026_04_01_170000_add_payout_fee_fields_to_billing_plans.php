<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('billing_plans')) {
            return;
        }

        Schema::table('billing_plans', function (Blueprint $table): void {
            if (!Schema::hasColumn('billing_plans', 'platform_take_rate_percent')) {
                $table->decimal('platform_take_rate_percent', 5, 2)->default(0)->after('annual_price');
            }
            if (!Schema::hasColumn('billing_plans', 'withdrawal_fee_percent')) {
                $table->decimal('withdrawal_fee_percent', 5, 2)->default(0)->after('platform_take_rate_percent');
            }
        });

        DB::table('billing_plans')->where('code', 'starter')->update([
            'platform_take_rate_percent' => 2.50,
            'withdrawal_fee_percent' => 1.00,
            'updated_at' => now(),
        ]);
        DB::table('billing_plans')->where('code', 'pro')->update([
            'platform_take_rate_percent' => 1.50,
            'withdrawal_fee_percent' => 0.75,
            'updated_at' => now(),
        ]);
        DB::table('billing_plans')->where('code', 'enterprise')->update([
            'platform_take_rate_percent' => 0.80,
            'withdrawal_fee_percent' => 0.50,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('billing_plans')) {
            return;
        }

        Schema::table('billing_plans', function (Blueprint $table): void {
            if (Schema::hasColumn('billing_plans', 'withdrawal_fee_percent')) {
                $table->dropColumn('withdrawal_fee_percent');
            }
            if (Schema::hasColumn('billing_plans', 'platform_take_rate_percent')) {
                $table->dropColumn('platform_take_rate_percent');
            }
        });
    }
};
