<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('billing_payment_transactions')) {
            return;
        }

        Schema::table('billing_payment_transactions', function (Blueprint $table): void {
            if (!Schema::hasColumn('billing_payment_transactions', 'ecommerce_order_id')) {
                $table->uuid('ecommerce_order_id')->nullable()->after('billing_plan_id')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('billing_payment_transactions')) {
            return;
        }

        if (Schema::hasColumn('billing_payment_transactions', 'ecommerce_order_id')) {
            Schema::table('billing_payment_transactions', function (Blueprint $table): void {
                $table->dropColumn('ecommerce_order_id');
            });
        }
    }
};
