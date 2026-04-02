<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('merchant_wallet_balances')) {
            Schema::create('merchant_wallet_balances', function (Blueprint $table): void {
                $table->id();
                $table->string('tenant_id', 64);
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('available_balance', 14, 2)->default(0);
                $table->decimal('pending_balance', 14, 2)->default(0);
                $table->decimal('locked_balance', 14, 2)->default(0);
                $table->timestamps();
                $table->index('tenant_id', 'mwb_tenant_idx');
                $table->unique(['tenant_id', 'currency_code'], 'mwb_tenant_currency_unique');
            });
        }

        if (!Schema::hasTable('merchant_wallet_ledger_entries')) {
            Schema::create('merchant_wallet_ledger_entries', function (Blueprint $table): void {
                $table->id();
                $table->string('tenant_id', 64);
                $table->string('shop_id', 64)->nullable();
                $table->unsignedBigInteger('billing_payment_transaction_id')->nullable();
                $table->uuid('ecommerce_order_id')->nullable();
                $table->string('entry_type', 50); // payment_settlement, withdrawal_request, withdrawal_completed
                $table->string('direction', 20)->default('credit'); // credit|debit
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('gross_amount', 14, 2)->default(0);
                $table->decimal('platform_fee_amount', 14, 2)->default(0);
                $table->decimal('gateway_fee_amount', 14, 2)->default(0);
                $table->decimal('net_amount', 14, 2)->default(0);
                $table->decimal('running_available_balance', 14, 2)->nullable();
                $table->decimal('running_pending_balance', 14, 2)->nullable();
                $table->decimal('running_locked_balance', 14, 2)->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index('tenant_id', 'mwle_tenant_idx');
                $table->index('shop_id', 'mwle_shop_idx');
                $table->index('billing_payment_transaction_id', 'mwle_bpt_idx');
                $table->index('ecommerce_order_id', 'mwle_order_idx');
                $table->index('entry_type', 'mwle_type_idx');
            });
        }

        if (!Schema::hasTable('merchant_withdrawal_requests')) {
            Schema::create('merchant_withdrawal_requests', function (Blueprint $table): void {
                $table->id();
                $table->string('tenant_id', 64);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('requested_amount', 14, 2);
                $table->decimal('fee_amount', 14, 2)->default(0);
                $table->decimal('net_amount', 14, 2)->default(0);
                $table->string('destination_type', 30)->default('mobile_money'); // mobile_money|bank|wallet
                $table->string('destination_reference', 190)->nullable();
                $table->string('status', 30)->default('pending'); // pending|approved|rejected|paid|failed
                $table->unsignedBigInteger('approved_by_user_id')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index('tenant_id', 'mwr_tenant_idx');
                $table->index('user_id', 'mwr_user_idx');
                $table->index('status', 'mwr_status_idx');
                $table->index('approved_by_user_id', 'mwr_approved_by_idx');
            });
        }

        if (!Schema::hasTable('payment_webhook_events')) {
            Schema::create('payment_webhook_events', function (Blueprint $table): void {
                $table->id();
                $table->string('provider', 50);
                $table->string('event_key', 190)->unique();
                $table->string('event_type', 120)->nullable();
                $table->unsignedBigInteger('billing_payment_transaction_id')->nullable();
                $table->string('status', 30)->default('processed');
                $table->json('payload')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
                $table->index('provider', 'pwe_provider_idx');
                $table->index('event_type', 'pwe_event_type_idx');
                $table->index('billing_payment_transaction_id', 'pwe_bpt_idx');
                $table->index('status', 'pwe_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('merchant_withdrawal_requests');
        Schema::dropIfExists('merchant_wallet_ledger_entries');
        Schema::dropIfExists('merchant_wallet_balances');
    }
};
