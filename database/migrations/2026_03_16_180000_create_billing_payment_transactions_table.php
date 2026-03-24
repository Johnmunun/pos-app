<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('billing_payment_transactions')) {
            return;
        }

        Schema::create('billing_payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('billing_plan_id')->index();
            $table->string('provider', 50)->default('fusionpay');
            $table->string('payment_method', 40);
            $table->decimal('amount', 12, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->string('status', 40)->default('pending')->index();
            $table->string('provider_reference')->nullable()->index();
            $table->string('checkout_url')->nullable();
            $table->json('provider_payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_payment_transactions');
    }
};
