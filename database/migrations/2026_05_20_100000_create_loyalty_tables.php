<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('loyalty_settings')) {
            Schema::create('loyalty_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->primary();
                $table->boolean('enabled')->default(false);
                $table->decimal('earn_amount_per_point', 12, 4)->default(1);
                $table->unsignedInteger('points_per_earn_unit')->default(1);
                $table->decimal('redeem_value_per_point', 12, 4)->default(0.05);
                $table->unsignedInteger('min_points_redeem')->default(100);
                $table->decimal('max_discount_percent', 5, 2)->default(50);
                $table->unsignedInteger('points_expire_days')->nullable();
                $table->json('tier_thresholds')->nullable();
                $table->json('module_rules')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('loyalty_accounts')) {
            Schema::create('loyalty_accounts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('module', 20)->index();
                $table->string('customer_id', 64)->index();
                $table->string('loyalty_number', 32);
                $table->string('tier', 20)->default('bronze');
                $table->integer('points_balance')->default(0);
                $table->integer('lifetime_points')->default(0);
                $table->string('status', 20)->default('active');
                $table->timestamps();

                $table->unique(['tenant_id', 'loyalty_number']);
                $table->unique(['tenant_id', 'module', 'customer_id']);
                $table->index(['tenant_id', 'status']);
            });
        }

        if (!Schema::hasTable('loyalty_transactions')) {
            Schema::create('loyalty_transactions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('loyalty_account_id')->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('type', 20);
                $table->integer('points');
                $table->integer('balance_after');
                $table->string('module', 20)->nullable();
                $table->string('sale_id', 64)->nullable()->index();
                $table->string('description')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['loyalty_account_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('loyalty_sale_links')) {
            Schema::create('loyalty_sale_links', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('module', 20);
                $table->string('sale_id', 64);
                $table->uuid('loyalty_account_id')->index();
                $table->string('customer_id', 64);
                $table->integer('points_earned')->default(0);
                $table->integer('points_redeemed')->default(0);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('eligible_amount', 12, 2)->default(0);
                $table->string('status', 20)->default('active');
                $table->timestamps();

                $table->unique(['tenant_id', 'module', 'sale_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_sale_links');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('loyalty_accounts');
        Schema::dropIfExists('loyalty_settings');
    }
};
