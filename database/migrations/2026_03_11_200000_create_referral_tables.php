<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('referral_accounts')) {
            Schema::create('referral_accounts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->uuid('parent_id')->nullable()->index()->comment('Compte referral du parrain direct (multi-niveau)');
                $table->string('code')->unique()->comment('Code / identifiant de parrainage unique');
                $table->unsignedInteger('total_referrals')->default(0);
                $table->decimal('total_referred_revenue', 18, 2)->default(0);
                $table->decimal('total_commissions_amount', 18, 2)->default(0);
                $table->string('currency', 3)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('referral_settings')) {
            Schema::create('referral_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->primary();
                $table->boolean('enabled')->default(false);
                $table->enum('commission_type', ['percentage', 'fixed'])->default('percentage');
                $table->decimal('commission_value', 10, 4)->default(0);
                $table->unsignedTinyInteger('max_levels')->default(1);
                $table->json('enabled_modules')->nullable()->comment('Liste des modules où le referral est actif');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('referral_commissions')) {
            Schema::create('referral_commissions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->uuid('referrer_account_id')->index();
                $table->unsignedBigInteger('referred_user_id')->nullable()->index();
                $table->string('source_type', 100)->index()->comment('Ex: pharmacy_sale, hardware_sale, commerce_sale, ecommerce_order');
                $table->string('source_id', 100)->index();
                $table->unsignedTinyInteger('level')->default(1)->comment('Niveau de parrainage (1, 2, 3...)');
                $table->decimal('amount', 18, 2);
                $table->string('currency', 3)->default('USD');
                $table->enum('status', ['pending', 'confirmed', 'paid'])->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_commissions');
        Schema::dropIfExists('referral_settings');
        Schema::dropIfExists('referral_accounts');
    }
};

