<?php

namespace Src\Infrastructure\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use App\Services\CurrencyConversionService;
use Src\Application\Billing\Services\BillingPlanService;
use Src\Infrastructure\Billing\Models\BillingPaymentTransaction;
use Src\Infrastructure\Billing\Services\FusionPayClient;

class BillingPaymentController extends Controller
{
    public function __construct(
        private readonly FusionPayClient $fusionPayClient,
        private readonly BillingPlanService $billingPlanService,
        private readonly CurrencyConversionService $currencyConversionService,
    ) {
    }

    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'billing_plan_id' => ['required', 'integer', 'exists:billing_plans,id'],
            'payment_method' => ['required', 'string', 'in:mobile_money,card'],
            'phone' => ['required', 'string', 'max:30'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
        ]);

        $plan = DB::table('billing_plans')
            ->where('id', $validated['billing_plan_id'])
            ->where('is_active', true)
            ->first();

        if (!$plan) {
            return response()->json(['message' => 'Plan introuvable ou inactif.'], 422);
        }

        $amount = (float) ($plan->monthly_price ?? 0);
        if ($amount <= 0) {
            return response()->json(['message' => 'Le plan choisi est gratuit. Aucun paiement requis.'], 422);
        }

        $user = Auth::user();
        $planCurrency = strtoupper((string) ($plan->currency_code ?? 'USD'));
        $fusionCurrency = strtoupper((string) config('fusionpay.payin_currency', 'CDF'));
        $convertedAmount = $this->currencyConversionService->convert(
            $amount,
            $planCurrency,
            $fusionCurrency,
            (int) ($user->tenant_id ?? 0)
        );

        if ($planCurrency !== $fusionCurrency && $convertedAmount === null) {
            return response()->json([
                'message' => sprintf(
                    'Aucun taux de change configure pour convertir %s vers %s. Veuillez configurer le taux dans les devises avant de lancer le paiement.',
                    $planCurrency,
                    $fusionCurrency
                ),
            ], 422);
        }

        $fusionAmount = (float) round((float) ($convertedAmount ?? $amount), 0);
        $minimumFusionAmount = (float) config('fusionpay.minimum_amount', 200);

        if ($fusionAmount <= $minimumFusionAmount) {
            return response()->json([
                'message' => sprintf(
                    'Montant minimum requis pour payer: %.0f %s. Votre plan actuel equivaut a %.0f %s. Augmentez le prix du plan ou le taux de change.',
                    $minimumFusionAmount + 1,
                    $fusionCurrency,
                    $fusionAmount,
                    $fusionCurrency
                ),
            ], 422);
        }

        $transaction = BillingPaymentTransaction::create([
            'tenant_id' => $user->tenant_id ?? null,
            'user_id' => $user->id ?? null,
            'billing_plan_id' => (int) $plan->id,
            'payment_method' => $validated['payment_method'],
            'amount' => $fusionAmount,
            'currency_code' => $fusionCurrency,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(20),
            'metadata' => [
                'plan_code' => $plan->code ?? null,
                'plan_price_original' => $amount,
                'plan_currency_original' => $planCurrency,
                'customer_name' => $validated['customer_name'] ?? ($user->name ?? null),
                'customer_email' => $validated['customer_email'] ?? ($user->email ?? null),
                'phone' => $validated['phone'] ?? null,
            ],
        ]);

        $callbackUrl = config('fusionpay.callback_url') ?: route('billing.payments.callback');

        $providerPayload = [
            'totalPrice' => $fusionAmount,
            'article' => [
                [
                    (string) ($plan->name ?? $plan->code ?? 'Plan') => $fusionAmount,
                ],
            ],
            'personal_Info' => [
                [
                    'userId' => $transaction->user_id,
                    'orderId' => $transaction->id,
                    'tenantId' => $transaction->tenant_id,
                    'planId' => $plan->id,
                    'planCode' => $plan->code ?? null,
                ],
            ],
            'numeroSend' => preg_replace('/\s+/', '', (string) $validated['phone']),
            'nomclient' => (string) $validated['customer_name'],
            'return_url' => route('billing.payments.show', $transaction->id),
            'webhook_url' => $callbackUrl,
        ];

        $providerResponse = $this->fusionPayClient->initiatePayment($providerPayload);
        $providerRaw = $providerResponse['raw'] ?? [];
        $providerAccepted = filter_var(data_get($providerRaw, 'statut', false), FILTER_VALIDATE_BOOL);
        $providerMessage = (string) (data_get($providerRaw, 'message') ?? '');

        $transaction->update([
            'provider_reference' => $providerResponse['provider_reference'],
            'checkout_url' => $providerResponse['checkout_url'],
            'provider_payload' => $providerRaw,
            'status' => strtolower((string) ($providerResponse['status'] ?? 'pending')) ?: 'pending',
        ]);

        if (!$providerAccepted || empty($providerResponse['checkout_url'])) {
            $transaction->update([
                'status' => 'failed',
            ]);

            return response()->json([
                'message' => $providerMessage !== '' ? $providerMessage : 'FusionPay a refuse la demande de paiement.',
            ], 422);
        }

        return response()->json([
            'transaction_id' => $transaction->id,
            'status' => $transaction->status,
            'checkout_url' => $transaction->checkout_url,
            'provider_reference' => $transaction->provider_reference, // tokenPay
        ]);
    }

    public function callback(Request $request): JsonResponse
    {
        if (!$this->isValidWebhook($request)) {
            return response()->json(['message' => 'Signature webhook invalide'], 403);
        }

        $event = strtolower((string) $request->input('event', ''));
        $providerReference = (string) ($request->input('tokenPay') ?? $request->input('token') ?? $request->input('provider_reference'));
        $transactionId = (int) ($request->input('personal_Info.0.orderId') ?? $request->input('metadata.transaction_id') ?? 0);
        $status = strtolower((string) ($request->input('statut') ?: $event ?: 'pending'));

        $transaction = BillingPaymentTransaction::query()
            ->when($transactionId > 0, fn ($q) => $q->where('id', $transactionId))
            ->when($providerReference !== '', fn ($q) => $q->orWhere('provider_reference', $providerReference))
            ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction introuvable'], 404);
        }

        if ($transaction->status === 'paid') {
            return response()->json(['message' => 'Transaction deja traitee'], 200);
        }

        $isPaid = in_array($status, ['paid', 'success', 'successful', 'completed', 'payin.session.completed'], true);
        $isFailed = in_array($status, ['failure', 'failed', 'cancelled', 'canceled', 'payin.session.cancelled', 'no paid'], true);
        $nextStatus = $isPaid ? 'paid' : ($isFailed ? 'failed' : 'pending');

        $transaction->update([
            'status' => $nextStatus,
            'paid_at' => $isPaid ? Carbon::now() : null,
            'provider_payload' => $request->all(),
        ]);

        if ($isPaid && !empty($transaction->tenant_id)) {
            $this->billingPlanService->assignTenantPlan(
                (string) $transaction->tenant_id,
                (int) $transaction->billing_plan_id,
                'active'
            );
        }

        return response()->json(['ok' => true, 'status' => $transaction->status]);
    }

    public function status(int $id): JsonResponse
    {
        $user = Auth::user();
        $transaction = BillingPaymentTransaction::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $providerStatus = null;
        if ($transaction->provider_reference) {
            try {
                $verify = $this->fusionPayClient->verifyPayment((string) $transaction->provider_reference);
                $providerStatus = strtolower((string) (data_get($verify, 'data.statut') ?? data_get($verify, 'statut') ?? 'pending'));
                $mappedStatus = in_array($providerStatus, ['paid', 'payin.session.completed'], true)
                    ? 'paid'
                    : (in_array($providerStatus, ['failure', 'cancelled', 'no paid', 'payin.session.cancelled'], true) ? 'failed' : 'pending');

                if ($mappedStatus !== $transaction->status) {
                    $transaction->status = $mappedStatus;
                    $transaction->provider_payload = $verify;
                    if ($mappedStatus === 'paid' && $transaction->paid_at === null) {
                        $transaction->paid_at = Carbon::now();
                        if (!empty($transaction->tenant_id)) {
                            $this->billingPlanService->assignTenantPlan(
                                (string) $transaction->tenant_id,
                                (int) $transaction->billing_plan_id,
                                'active'
                            );
                        }
                    }
                    $transaction->save();
                }
            } catch (\Throwable $e) {
                // Keep local status when provider check fails.
            }
        }

        return response()->json([
            'id' => $transaction->id,
            'status' => $transaction->status,
            'provider_status' => $providerStatus,
            'checkout_url' => $transaction->checkout_url,
            'provider_reference' => $transaction->provider_reference,
            'amount' => $transaction->amount,
            'currency_code' => $transaction->currency_code,
            'paid_at' => $transaction->paid_at,
            'expires_at' => $transaction->expires_at,
        ]);
    }

    public function latest(Request $request): JsonResponse
    {
        $user = $request->user();

        $transaction = BillingPaymentTransaction::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if (!$transaction) {
            return response()->json(['transaction' => null]);
        }

        return response()->json([
            'transaction' => [
                'id' => $transaction->id,
                'status' => $transaction->status,
                'checkout_url' => $transaction->checkout_url,
                'provider_reference' => $transaction->provider_reference,
                'amount' => $transaction->amount,
                'currency_code' => $transaction->currency_code,
                'billing_plan_id' => $transaction->billing_plan_id,
                'paid_at' => $transaction->paid_at,
                'expires_at' => $transaction->expires_at,
            ],
        ]);
    }

    public function showStatusPage(int $id): InertiaResponse
    {
        $user = Auth::user();

        $transaction = BillingPaymentTransaction::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return Inertia::render('Billing/PaymentStatus', [
            'transaction' => [
                'id' => $transaction->id,
                'status' => $transaction->status,
                'checkout_url' => $transaction->checkout_url,
                'provider_reference' => $transaction->provider_reference,
                'amount' => $transaction->amount,
                'currency_code' => $transaction->currency_code,
                'billing_plan_id' => $transaction->billing_plan_id,
                'paid_at' => $transaction->paid_at,
                'expires_at' => $transaction->expires_at,
            ],
        ]);
    }

    public function showOnboardingPaymentPage(): InertiaResponse
    {
        return Inertia::render('Billing/OnboardingPayment');
    }

    private function isValidWebhook(Request $request): bool
    {
        $secret = (string) config('fusionpay.webhook_secret');

        if ($secret === '') {
            return true;
        }

        $signature = (string) $request->header('X-Fusionpay-Signature', '');
        if ($signature === '') {
            return false;
        }

        $computed = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($computed, $signature);
    }
}
