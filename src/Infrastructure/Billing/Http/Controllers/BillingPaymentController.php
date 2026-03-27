<?php

namespace Src\Infrastructure\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\RequestException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use App\Services\CurrencyConversionService;
use Src\Application\Billing\Services\BillingPlanService;
use Src\Infrastructure\Billing\Models\BillingPaymentTransaction;
use Src\Infrastructure\Ecommerce\Models\OrderItemModel;
use Src\Infrastructure\Ecommerce\Models\OrderModel;
use Src\Infrastructure\Billing\Services\FusionPayClient;
use Src\Application\Ecommerce\UseCases\UpdatePaymentStatusUseCase;
use Src\Domain\Ecommerce\Entities\Order as EcommerceOrderEntity;

class BillingPaymentController extends Controller
{
    public function __construct(
        private readonly FusionPayClient $fusionPayClient,
        private readonly BillingPlanService $billingPlanService,
        private readonly CurrencyConversionService $currencyConversionService,
        private readonly UpdatePaymentStatusUseCase $updateEcommercePaymentStatusUseCase,
    ) {
    }

    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'billing_plan_id' => ['required', 'integer', 'exists:billing_plans,id'],
            'payment_method' => ['required', 'string', 'in:mobile_money,card'],
            'phone' => ['required', 'string', 'max:30'],
            'billing_cycle' => ['nullable', 'string', 'in:monthly,annual'],
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

        $billingCycle = (string) ($validated['billing_cycle'] ?? 'monthly');
        $amount = $billingCycle === 'annual'
            ? (float) ($plan->annual_price ?? 0)
            : (float) ($plan->monthly_price ?? 0);

        if ($billingCycle === 'annual' && ($plan->annual_price === null || (float) $plan->annual_price <= 0)) {
            return response()->json(['message' => 'Ce plan ne propose pas de paiement annuel.'], 422);
        }
        if ($amount <= 0) {
            return response()->json(['message' => 'Le plan choisi est gratuit. Aucun paiement requis.'], 422);
        }

        $user = Auth::user();
        $resolvedPhone = $validated['phone'];

        $planCurrency = strtoupper((string) ($plan->currency_code ?? 'USD'));
        $fusionCurrency = strtoupper((string) config('fusionpay.payin_currency', 'CDF'));
        $convertedAmount = $this->currencyConversionService->convert(
            $amount,
            $planCurrency,
            $fusionCurrency,
            0
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
                'billing_cycle' => $billingCycle,
                'customer_name' => $validated['customer_name'] ?? ($user->name ?? null),
                'customer_email' => $validated['customer_email'] ?? ($user->email ?? null),
                'phone' => $resolvedPhone,
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
            'numeroSend' => preg_replace('/\s+/', '', (string) $resolvedPhone),
            'nomclient' => (string) $validated['customer_name'],
            'return_url' => route('billing.payments.show', $transaction->id),
            'webhook_url' => $callbackUrl,
        ];

        try {
            $providerResponse = $this->fusionPayClient->initiatePayment($providerPayload);
        } catch (RequestException $e) {
            $status = $e->response->status();
            $body = $e->response->json();
            $rawMessage = is_array($body) ? (string) (data_get($body, 'message') ?? data_get($body, 'error') ?? '') : '';
            $fallback = 'FusionPay a rejeté la demande de paiement.';

            $transaction->update([
                'status' => 'failed',
                'provider_payload' => [
                    'error' => true,
                    'http_status' => $status,
                    'body' => $body,
                ],
            ]);

            $payload = [
                'message' => $rawMessage !== '' ? $rawMessage : $fallback,
                'provider_http_status' => $status,
            ];
            if ((bool) config('app.debug')) {
                $payload['provider_body'] = $body;
                $payload['provider_url'] = config('fusionpay.api_link') ?: (rtrim((string) config('fusionpay.base_url'), '/') . (string) config('fusionpay.endpoints.init_payment'));
                $payload['sent_payload'] = $providerPayload;
            }

            return response()->json($payload, 422);
        } catch (\Throwable $e) {
            $transaction->update([
                'status' => 'failed',
                'provider_payload' => [
                    'error' => true,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ],
            ]);

            return response()->json([
                'message' => (bool) config('app.debug')
                    ? ('Erreur: ' . $e->getMessage())
                    : 'Une erreur est survenue pendant l’initiation du paiement.',
            ], 422);
        }
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

        if ($isPaid && !empty($transaction->ecommerce_order_id)) {
            try {
                $this->updateEcommercePaymentStatusUseCase->execute(
                    (string) $transaction->ecommerce_order_id,
                    EcommerceOrderEntity::PAYMENT_STATUS_PAID
                );
            } catch (\Throwable) {
                // Ne pas échouer le webhook si la commande est déjà à jour.
            }
        } elseif ($isPaid && !empty($transaction->tenant_id) && !empty($transaction->billing_plan_id)) {
            $cycle = strtolower((string) data_get($transaction->metadata, 'billing_cycle', 'monthly'));
            $endsAt = $cycle === 'annual'
                ? now()->addYear()
                : now()->addMonth();
            $this->billingPlanService->assignTenantPlan(
                (string) $transaction->tenant_id,
                (int) $transaction->billing_plan_id,
                'active',
                $endsAt->toDateTimeString()
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
                        if (!empty($transaction->ecommerce_order_id)) {
                            try {
                                $this->updateEcommercePaymentStatusUseCase->execute(
                                    (string) $transaction->ecommerce_order_id,
                                    EcommerceOrderEntity::PAYMENT_STATUS_PAID
                                );
                            } catch (\Throwable) {
                            }
                        } elseif (!empty($transaction->tenant_id) && !empty($transaction->billing_plan_id)) {
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

        $transaction->refresh();

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
            'ecommerce_success_url' => $this->resolveEcommercePaymentSuccessUrl($transaction),
            'has_ecommerce_order' => filled($transaction->ecommerce_order_id),
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

    public function showStatusPage(int $id): InertiaResponse|RedirectResponse
    {
        $user = Auth::user();

        $transaction = BillingPaymentTransaction::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $ecommerceSuccessUrl = $this->resolveEcommercePaymentSuccessUrl($transaction);

        if ($ecommerceSuccessUrl !== null && $this->isTransactionPaid($transaction)) {
            return redirect()->to($ecommerceSuccessUrl);
        }

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
                'ecommerce_success_url' => $ecommerceSuccessUrl,
                'has_ecommerce_order' => filled($transaction->ecommerce_order_id),
            ],
        ]);
    }

    public function showOnboardingPaymentPage(): InertiaResponse
    {
        return Inertia::render('Billing/OnboardingPayment');
    }

    private function isTransactionPaid(BillingPaymentTransaction $transaction): bool
    {
        return in_array(strtolower((string) $transaction->status), ['paid', 'success', 'completed'], true);
    }

    /**
     * URL de la page vitrine « paiement réussi » avec liens de téléchargement (FusionPay e-commerce).
     */
    private function resolveEcommercePaymentSuccessUrl(BillingPaymentTransaction $transaction): ?string
    {
        $orderId = $transaction->ecommerce_order_id;
        if ($orderId === null || $orderId === '') {
            return null;
        }

        $order = OrderModel::find($orderId);
        if (!$order || strtolower((string) $order->payment_status) !== 'paid') {
            return null;
        }

        $token = OrderItemModel::query()
            ->where('order_id', $order->id)
            ->whereNotNull('download_token')
            ->orderBy('id')
            ->value('download_token');

        return $token ? route('ecommerce.payment.success', ['token' => $token]) : null;
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
