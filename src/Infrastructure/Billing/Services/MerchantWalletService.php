<?php

namespace Src\Infrastructure\Billing\Services;

use App\Models\User;
use App\Services\AppNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Src\Infrastructure\Billing\Models\BillingPaymentTransaction;
use Src\Infrastructure\Billing\Models\MerchantWalletBalance;
use Src\Infrastructure\Billing\Models\MerchantWalletLedgerEntry;
use Src\Infrastructure\Billing\Models\MerchantWithdrawalRequest;
use Src\Infrastructure\Ecommerce\Models\OrderModel;

class MerchantWalletService
{
    public function __construct(
        private readonly AppNotificationService $appNotificationService,
    ) {
    }

    public function applySettlementFromEcommercePayment(BillingPaymentTransaction $transaction): void
    {
        if (!Schema::hasTable('merchant_wallet_ledger_entries') || !Schema::hasTable('merchant_wallet_balances')) {
            return;
        }

        if (empty($transaction->ecommerce_order_id)) {
            return;
        }

        DB::transaction(function () use ($transaction): void {
            $existing = MerchantWalletLedgerEntry::query()
                ->where('billing_payment_transaction_id', $transaction->id)
                ->where('entry_type', 'payment_settlement')
                ->first();
            if ($existing !== null) {
                return;
            }

            $order = OrderModel::query()->find($transaction->ecommerce_order_id);
            if ($order === null) {
                return;
            }

            $tenantId = $this->resolveTenantIdFromShop((string) $order->shop_id);
            if ($tenantId === null) {
                return;
            }

            $currency = strtoupper((string) ($order->currency ?: $transaction->currency_code ?: 'USD'));
            $gross = (float) $order->total_amount;
            if ($gross <= 0) {
                return;
            }

            $feeRate = $this->resolvePlatformTakeRatePercent($tenantId);
            $platformFee = round(($gross * $feeRate) / 100, 2);
            $gatewayFee = 0.0;
            $net = round(max(0, $gross - $platformFee - $gatewayFee), 2);

            $balance = MerchantWalletBalance::query()->firstOrCreate(
                ['tenant_id' => (string) $tenantId, 'currency_code' => $currency],
                ['available_balance' => 0, 'pending_balance' => 0, 'locked_balance' => 0]
            );

            $balance->available_balance = round((float) $balance->available_balance + $net, 2);
            $balance->save();

            MerchantWalletLedgerEntry::query()->create([
                'tenant_id' => (string) $tenantId,
                'shop_id' => (string) $order->shop_id,
                'billing_payment_transaction_id' => $transaction->id,
                'ecommerce_order_id' => (string) $order->id,
                'entry_type' => 'payment_settlement',
                'direction' => 'credit',
                'currency_code' => $currency,
                'gross_amount' => $gross,
                'platform_fee_amount' => $platformFee,
                'gateway_fee_amount' => $gatewayFee,
                'net_amount' => $net,
                'running_available_balance' => (float) $balance->available_balance,
                'running_pending_balance' => (float) $balance->pending_balance,
                'running_locked_balance' => (float) $balance->locked_balance,
                'meta' => [
                    'order_number' => $order->order_number,
                    'platform_take_rate_percent' => $feeRate,
                    'source' => 'fusionpay_webhook',
                ],
            ]);

            $title = 'Paiement e-commerce reçu';
            $body = sprintf(
                'Commande %s payée. Brut: %.2f %s, commission plateforme: %.2f %s, net crédité: %.2f %s.',
                (string) ($order->order_number ?: $order->id),
                $gross,
                $currency,
                $platformFee,
                $currency,
                $net,
                $currency
            );
            $this->appNotificationService->notifyEcommerceOrder($title, $body, (int) $tenantId);
        });
    }

    public function createWithdrawalRequest(User $user, array $payload): MerchantWithdrawalRequest
    {
        if (!Schema::hasTable('merchant_wallet_balances') || !Schema::hasTable('merchant_withdrawal_requests')) {
            throw new RuntimeException('Module retrait indisponible.');
        }

        $tenantId = (string) ($user->tenant_id ?? '');
        if ($tenantId === '') {
            throw new RuntimeException('Tenant introuvable.');
        }

        $currency = strtoupper((string) ($payload['currency_code'] ?? 'USD'));
        $amount = round((float) ($payload['requested_amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new RuntimeException('Montant de retrait invalide.');
        }

        return DB::transaction(function () use ($tenantId, $user, $currency, $amount, $payload): MerchantWithdrawalRequest {
            $balance = MerchantWalletBalance::query()->lockForUpdate()->firstOrCreate(
                ['tenant_id' => $tenantId, 'currency_code' => $currency],
                ['available_balance' => 0, 'pending_balance' => 0, 'locked_balance' => 0]
            );

            if ((float) $balance->available_balance < $amount) {
                throw new RuntimeException('Solde disponible insuffisant pour ce retrait.');
            }

            $withdrawFeePercent = $this->resolveWithdrawalFeePercent($tenantId);
            $feeAmount = round(($amount * $withdrawFeePercent) / 100, 2);
            $netAmount = round(max(0, $amount - $feeAmount), 2);

            $balance->available_balance = round((float) $balance->available_balance - $amount, 2);
            $balance->locked_balance = round((float) $balance->locked_balance + $amount, 2);
            $balance->save();

            $request = MerchantWithdrawalRequest::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'currency_code' => $currency,
                'requested_amount' => $amount,
                'fee_amount' => $feeAmount,
                'net_amount' => $netAmount,
                'destination_type' => (string) ($payload['destination_type'] ?? 'mobile_money'),
                'destination_reference' => (string) ($payload['destination_reference'] ?? ''),
                'status' => 'pending',
                'meta' => [
                    'withdrawal_fee_percent' => $withdrawFeePercent,
                ],
            ]);

            MerchantWalletLedgerEntry::query()->create([
                'tenant_id' => $tenantId,
                'shop_id' => null,
                'billing_payment_transaction_id' => null,
                'ecommerce_order_id' => null,
                'entry_type' => 'withdrawal_request',
                'direction' => 'debit',
                'currency_code' => $currency,
                'gross_amount' => $amount,
                'platform_fee_amount' => $feeAmount,
                'gateway_fee_amount' => 0,
                'net_amount' => $netAmount,
                'running_available_balance' => (float) $balance->available_balance,
                'running_pending_balance' => (float) $balance->pending_balance,
                'running_locked_balance' => (float) $balance->locked_balance,
                'meta' => [
                    'withdrawal_request_id' => $request->id,
                    'destination_type' => $request->destination_type,
                ],
            ]);

            $title = 'Demande de retrait créée';
            $body = sprintf(
                'Votre demande de retrait de %.2f %s a été enregistrée (net estimé %.2f %s).',
                $amount,
                $currency,
                $netAmount,
                $currency
            );
            $this->appNotificationService->notifyEcommerceOrder($title, $body, (int) $tenantId);

            return $request;
        });
    }

    private function resolveTenantIdFromShop(string $shopId): ?string
    {
        if ($shopId === '' || !Schema::hasTable('shops')) {
            return null;
        }

        $tenantId = DB::table('shops')->where('id', $shopId)->value('tenant_id');
        if ($tenantId === null || $tenantId === '') {
            return null;
        }

        return (string) $tenantId;
    }

    private function resolvePlatformTakeRatePercent(string $tenantId): float
    {
        $row = DB::table('tenant_plan_subscriptions as tps')
            ->join('billing_plans as bp', 'bp.id', '=', 'tps.billing_plan_id')
            ->where('tps.tenant_id', $tenantId)
            ->where('tps.status', 'active')
            ->orderByDesc('tps.id')
            ->select('bp.platform_take_rate_percent')
            ->first();

        if ($row && isset($row->platform_take_rate_percent)) {
            return (float) $row->platform_take_rate_percent;
        }

        return 0.0;
    }

    private function resolveWithdrawalFeePercent(string $tenantId): float
    {
        $row = DB::table('tenant_plan_subscriptions as tps')
            ->join('billing_plans as bp', 'bp.id', '=', 'tps.billing_plan_id')
            ->where('tps.tenant_id', $tenantId)
            ->where('tps.status', 'active')
            ->orderByDesc('tps.id')
            ->select('bp.withdrawal_fee_percent')
            ->first();

        if ($row && isset($row->withdrawal_fee_percent)) {
            return (float) $row->withdrawal_fee_percent;
        }

        return 0.0;
    }
}
