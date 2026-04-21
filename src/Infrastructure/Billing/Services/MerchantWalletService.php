<?php

namespace Src\Infrastructure\Billing\Services;

use App\Models\User;
use App\Services\AppNotificationService;
use Illuminate\Support\Carbon;
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
                ->where('entry_type', 'payment_settlement')
                ->where(function ($q) use ($transaction): void {
                    $q->where('billing_payment_transaction_id', $transaction->id);
                    if (!empty($transaction->ecommerce_order_id)) {
                        $q->orWhere('ecommerce_order_id', (string) $transaction->ecommerce_order_id);
                    }
                })
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

    public function applySettlementFromEcommerceOrderId(string $orderId, string $source = 'manual_confirmation'): void
    {
        if (!Schema::hasTable('merchant_wallet_ledger_entries') || !Schema::hasTable('merchant_wallet_balances')) {
            return;
        }

        if ($orderId === '') {
            return;
        }

        DB::transaction(function () use ($orderId, $source): void {
            $existing = MerchantWalletLedgerEntry::query()
                ->where('entry_type', 'payment_settlement')
                ->where('ecommerce_order_id', $orderId)
                ->first();
            if ($existing !== null) {
                return;
            }

            $order = OrderModel::query()->find($orderId);
            if ($order === null || strtolower((string) $order->payment_status) !== 'paid') {
                return;
            }

            $tenantId = $this->resolveTenantIdFromShop((string) $order->shop_id);
            if ($tenantId === null) {
                return;
            }

            $currency = strtoupper((string) ($order->currency ?: 'USD'));
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
                'billing_payment_transaction_id' => null,
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
                    'source' => $source,
                ],
            ]);
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

    public function approveWithdrawalRequest(int $withdrawalId, User $admin): MerchantWithdrawalRequest
    {
        return DB::transaction(function () use ($withdrawalId, $admin): MerchantWithdrawalRequest {
            $request = MerchantWithdrawalRequest::query()->lockForUpdate()->find($withdrawalId);
            if ($request === null) {
                throw new RuntimeException('Demande de retrait introuvable.');
            }

            if ((string) $request->status !== 'pending') {
                throw new RuntimeException('Seules les demandes en attente peuvent etre approuvees.');
            }

            $request->status = 'approved';
            $request->approved_by_user_id = $admin->id;
            $request->approved_at = now();
            $request->rejection_reason = null;
            $request->save();

            $this->appendWithdrawalLedgerEntry($request, 'withdrawal_approved', 'debit');

            return $request;
        });
    }

    public function rejectWithdrawalRequest(int $withdrawalId, User $admin, string $reason = ''): MerchantWithdrawalRequest
    {
        return DB::transaction(function () use ($withdrawalId, $admin, $reason): MerchantWithdrawalRequest {
            $request = MerchantWithdrawalRequest::query()->lockForUpdate()->find($withdrawalId);
            if ($request === null) {
                throw new RuntimeException('Demande de retrait introuvable.');
            }

            if (!in_array((string) $request->status, ['pending', 'approved'], true)) {
                throw new RuntimeException('Cette demande ne peut plus etre rejetee.');
            }

            $balance = MerchantWalletBalance::query()->lockForUpdate()->firstOrCreate(
                ['tenant_id' => (string) $request->tenant_id, 'currency_code' => (string) $request->currency_code],
                ['available_balance' => 0, 'pending_balance' => 0, 'locked_balance' => 0]
            );

            $amount = round((float) $request->requested_amount, 2);
            $balance->locked_balance = round(max(0, (float) $balance->locked_balance - $amount), 2);
            $balance->available_balance = round((float) $balance->available_balance + $amount, 2);
            $balance->save();

            $request->status = 'rejected';
            $request->approved_by_user_id = $admin->id;
            $request->approved_at = $request->approved_at ?: now();
            $request->rejection_reason = trim($reason) !== '' ? trim($reason) : 'Demande rejetee par l\'administrateur.';
            $request->save();

            $this->appendWithdrawalLedgerEntry($request, 'withdrawal_rejected', 'credit', $balance, [
                'rejection_reason' => $request->rejection_reason,
            ]);

            return $request;
        });
    }

    public function completeWithdrawalRequest(int $withdrawalId, User $admin, array $meta = []): MerchantWithdrawalRequest
    {
        return DB::transaction(function () use ($withdrawalId, $admin, $meta): MerchantWithdrawalRequest {
            $request = MerchantWithdrawalRequest::query()->lockForUpdate()->find($withdrawalId);
            if ($request === null) {
                throw new RuntimeException('Demande de retrait introuvable.');
            }

            if (!in_array((string) $request->status, ['pending', 'approved'], true)) {
                throw new RuntimeException('Cette demande ne peut pas etre marquee comme payee.');
            }

            $balance = MerchantWalletBalance::query()->lockForUpdate()->firstOrCreate(
                ['tenant_id' => (string) $request->tenant_id, 'currency_code' => (string) $request->currency_code],
                ['available_balance' => 0, 'pending_balance' => 0, 'locked_balance' => 0]
            );

            $amount = round((float) $request->requested_amount, 2);
            if ((float) $balance->locked_balance < $amount) {
                throw new RuntimeException('Solde bloque insuffisant pour finaliser ce retrait.');
            }

            $balance->locked_balance = round((float) $balance->locked_balance - $amount, 2);
            $balance->save();

            $mergedMeta = array_merge((array) $request->meta, $meta);
            $request->meta = $mergedMeta;
            $request->status = 'paid';
            $request->approved_by_user_id = $request->approved_by_user_id ?: $admin->id;
            $request->approved_at = $request->approved_at ?: now();
            $request->paid_at = now();
            $request->rejection_reason = null;
            $request->save();

            $this->appendWithdrawalLedgerEntry($request, 'withdrawal_completed', 'debit', $balance, $meta);

            return $request;
        });
    }

    public function failWithdrawalRequest(int $withdrawalId, User $admin, string $reason = '', array $meta = []): MerchantWithdrawalRequest
    {
        return DB::transaction(function () use ($withdrawalId, $admin, $reason, $meta): MerchantWithdrawalRequest {
            $request = MerchantWithdrawalRequest::query()->lockForUpdate()->find($withdrawalId);
            if ($request === null) {
                throw new RuntimeException('Demande de retrait introuvable.');
            }

            if (!in_array((string) $request->status, ['pending', 'approved'], true)) {
                throw new RuntimeException('Cette demande ne peut pas etre marquee en echec.');
            }

            $balance = MerchantWalletBalance::query()->lockForUpdate()->firstOrCreate(
                ['tenant_id' => (string) $request->tenant_id, 'currency_code' => (string) $request->currency_code],
                ['available_balance' => 0, 'pending_balance' => 0, 'locked_balance' => 0]
            );

            $amount = round((float) $request->requested_amount, 2);
            $balance->locked_balance = round(max(0, (float) $balance->locked_balance - $amount), 2);
            $balance->available_balance = round((float) $balance->available_balance + $amount, 2);
            $balance->save();

            $requestMeta = array_merge((array) $request->meta, $meta);
            $request->meta = $requestMeta;
            $request->status = 'failed';
            $request->approved_by_user_id = $request->approved_by_user_id ?: $admin->id;
            $request->approved_at = $request->approved_at ?: now();
            $request->rejection_reason = trim($reason) !== '' ? trim($reason) : 'Echec de transfert.';
            $request->paid_at = null;
            $request->save();

            $this->appendWithdrawalLedgerEntry($request, 'withdrawal_failed', 'credit', $balance, [
                'failure_reason' => $request->rejection_reason,
            ] + $meta);

            return $request;
        });
    }

    private function appendWithdrawalLedgerEntry(
        MerchantWithdrawalRequest $request,
        string $entryType,
        string $direction,
        ?MerchantWalletBalance $balance = null,
        array $extraMeta = []
    ): void {
        if ($balance === null) {
            $balance = MerchantWalletBalance::query()->firstOrCreate(
                ['tenant_id' => (string) $request->tenant_id, 'currency_code' => (string) $request->currency_code],
                ['available_balance' => 0, 'pending_balance' => 0, 'locked_balance' => 0]
            );
        }

        MerchantWalletLedgerEntry::query()->create([
            'tenant_id' => (string) $request->tenant_id,
            'shop_id' => null,
            'billing_payment_transaction_id' => null,
            'ecommerce_order_id' => null,
            'entry_type' => $entryType,
            'direction' => $direction,
            'currency_code' => (string) $request->currency_code,
            'gross_amount' => (float) $request->requested_amount,
            'platform_fee_amount' => (float) $request->fee_amount,
            'gateway_fee_amount' => 0,
            'net_amount' => (float) $request->net_amount,
            'running_available_balance' => (float) $balance->available_balance,
            'running_pending_balance' => (float) $balance->pending_balance,
            'running_locked_balance' => (float) $balance->locked_balance,
            'meta' => array_merge([
                'withdrawal_request_id' => $request->id,
                'status' => (string) $request->status,
                'recorded_at' => Carbon::now()->toIso8601String(),
            ], $extraMeta),
        ]);
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
