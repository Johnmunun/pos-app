<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CurrencyConversionService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Src\Infrastructure\Billing\Models\BillingPaymentTransaction;
use Src\Infrastructure\Billing\Services\FusionPayClient;
use Src\Infrastructure\Ecommerce\Models\OrderModel;
use Src\Infrastructure\Ecommerce\Models\PaymentMethodModel;

class EcommerceFusionPaymentController extends Controller
{
    public function __construct(
        private readonly FusionPayClient $fusionPayClient,
        private readonly CurrencyConversionService $currencyConversionService,
    ) {
    }

    /**
     * Lance un paiement FusionPay pour une commande e-commerce (méthode type fusionpay).
     * Même principe que l'abonnement : redirection vers checkout_url.
     */
    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'uuid', 'exists:ecommerce_orders,id'],
            'payment_method' => ['required', 'string', 'in:mobile_money,card'],
            'phone' => ['required', 'string', 'max:30'],
            'customer_name' => ['required', 'string', 'max:255'],
        ]);

        $user = Auth::user();
        $storefrontShop = $request->attributes->get('storefront_shop');
        if ($user === null && $storefrontShop === null) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $order = OrderModel::query()->where('id', $validated['order_id'])->first();
        if ($order === null) {
            return response()->json(['message' => 'Commande introuvable.'], 404);
        }

        if (strtolower((string) $order->payment_status) !== 'pending') {
            return response()->json(['message' => 'Cette commande ne nécessite plus de paiement en ligne.'], 422);
        }

        $shopId = (string) $order->shop_id;
        if ($user !== null) {
            $userShop = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
            if ($userShop === null || $shopId !== (string) $userShop) {
                return response()->json(['message' => 'Accès à cette commande refusé.'], 403);
            }
        } else {
            $publicShopId = $storefrontShop ? (string) $storefrontShop->id : '';
            if ($publicShopId === '' || $publicShopId !== $shopId) {
                return response()->json(['message' => 'Accès à cette commande refusé.'], 403);
            }
        }

        // Tolérant: on autorise le lancement du paiement en ligne même si la méthode
        // de paiement n'est pas explicitement mappée sur la commande.
        $code = (string) ($order->payment_method ?? '');
        if ($code !== '') {
            $method = PaymentMethodModel::query()
                ->where('shop_id', $shopId)
                ->where('code', $code)
                ->where('is_active', true)
                ->first();
            if ($method !== null && strtolower((string) $method->type) !== 'fusionpay') {
                return response()->json(['message' => 'Cette commande n’utilise pas un mode de paiement en ligne.'], 422);
            }
        }

        $pending = BillingPaymentTransaction::query()
            ->where('ecommerce_order_id', $order->id)
            ->where('status', 'pending')
            ->where(function ($q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($pending !== null && $pending->checkout_url) {
            return response()->json([
                'transaction_id' => $pending->id,
                'checkout_url' => $pending->checkout_url,
                'provider_reference' => $pending->provider_reference,
                'reused' => true,
            ]);
        }

        $amount = (float) $order->total_amount;
        $orderCurrency = strtoupper((string) ($order->currency ?? 'USD'));
        $fusionCurrency = strtoupper((string) config('fusionpay.payin_currency', 'CDF'));
        $convertedAmount = $this->currencyConversionService->convert(
            $amount,
            $orderCurrency,
            $fusionCurrency,
            0
        );

        if ($orderCurrency !== $fusionCurrency && $convertedAmount === null) {
            return response()->json([
                'message' => sprintf(
                    'Aucun taux de change configuré pour convertir %s vers %s.',
                    $orderCurrency,
                    $fusionCurrency
                ),
            ], 422);
        }

        $fusionAmount = (float) round((float) ($convertedAmount ?? $amount), 0);
        $minimumFusionAmount = (float) config('fusionpay.minimum_amount', 200);

        if ($fusionAmount <= $minimumFusionAmount) {
            return response()->json([
                'message' => sprintf(
                    'Montant minimum FusionPay : %.0f %s (montant converti : %.0f %s).',
                    $minimumFusionAmount + 1,
                    $fusionCurrency,
                    $fusionAmount,
                    $fusionCurrency
                ),
            ], 422);
        }

        $tenantId = $user?->tenant_id !== null
            ? (string) $user?->tenant_id
            : (($storefrontShop && isset($storefrontShop->tenant_id)) ? (string) $storefrontShop->tenant_id : null);

        $placeholderPlanId = (int) DB::table('billing_plans')->orderBy('id')->value('id');
        if ($placeholderPlanId < 1) {
            return response()->json([
                'message' => 'Configuration des plans indisponible. Contactez le support.',
            ], 500);
        }

        $transaction = BillingPaymentTransaction::create([
            'tenant_id' => $tenantId,
            'user_id' => $user?->id,
            'billing_plan_id' => $placeholderPlanId,
            'ecommerce_order_id' => $order->id,
            'payment_method' => $validated['payment_method'],
            'amount' => $fusionAmount,
            'currency_code' => $fusionCurrency,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30),
            'metadata' => [
                'ecommerce_order_number' => $order->order_number,
                'order_currency_original' => $orderCurrency,
                'order_amount_original' => $amount,
                'customer_name' => $validated['customer_name'],
                'phone' => preg_replace('/\s+/', '', (string) $validated['phone']),
            ],
        ]);

        $label = 'Commande ' . ($order->order_number ?? $order->id);
        $callbackUrl = config('fusionpay.callback_url') ?: route('billing.payments.callback');

        $providerPayload = [
            'totalPrice' => $fusionAmount,
            'article' => [
                [
                    $label => $fusionAmount,
                ],
            ],
            'personal_Info' => [
                [
                    'userId' => $transaction->user_id,
                    'orderId' => $transaction->id,
                    'tenantId' => $transaction->tenant_id,
                    'ecommerceOrderId' => $order->id,
                    'shopId' => $shopId,
                ],
            ],
            'numeroSend' => preg_replace('/\s+/', '', (string) $validated['phone']),
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

            $transaction->update([
                'status' => 'failed',
                'provider_payload' => [
                    'error' => true,
                    'http_status' => $status,
                    'body' => $body,
                ],
            ]);

            return response()->json([
                'message' => $rawMessage !== '' ? $rawMessage : 'FusionPay a rejeté la demande de paiement.',
            ], 422);
        } catch (\Throwable $e) {
            $transaction->update([
                'status' => 'failed',
                'provider_payload' => [
                    'error' => true,
                    'message' => $e->getMessage(),
                ],
            ]);

            return response()->json([
                'message' => (bool) config('app.debug')
                    ? $e->getMessage()
                    : 'Erreur pendant l’initiation du paiement.',
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
            $transaction->update(['status' => 'failed']);

            return response()->json([
                'message' => $providerMessage !== '' ? $providerMessage : 'FusionPay a refusé la demande de paiement.',
            ], 422);
        }

        return response()->json([
            'transaction_id' => $transaction->id,
            'checkout_url' => $transaction->checkout_url,
            'provider_reference' => $transaction->provider_reference,
        ]);
    }
}
