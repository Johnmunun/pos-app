<?php

namespace Src\Infrastructure\Billing\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FusionPayClient
{
    public function initiatePayment(array $payload): array
    {
        $responseData = $this->post(
            $this->resolveInitUrl(),
            $payload
        );

        return [
            'raw' => $responseData,
            'provider_reference' => Arr::get($responseData, 'token')
                ?? Arr::get($responseData, 'tokenPay')
                ?? Arr::get($responseData, 'reference'),
            'checkout_url' => Arr::get($responseData, 'url')
                ?? Arr::get($responseData, 'payment_url')
                ?? Arr::get($responseData, 'url'),
            'status' => $this->normalizeStatus(
                Arr::get($responseData, 'data.statut')
                    ?? Arr::get($responseData, 'statut')
                    ?? Arr::get($responseData, 'status')
                    ?? 'pending'
            ),
        ];
    }

    public function verifyPayment(string $token): array
    {
        if (!config('fusionpay.enabled')) {
            throw new RuntimeException('FusionPay est desactive. Activez FUSIONPAY_ENABLED=true.');
        }

        try {
            $response = Http::timeout((int) config('fusionpay.timeout', 20))
                ->acceptJson()
                ->withHeaders($this->headers())
                ->get($this->resolveVerifyUrl($token))
                ->throw();

            return (array) $response->json();
        } catch (RequestException $exception) {
            Log::error('FusionPay verify request failed', [
                'token' => $token,
                'status' => optional($exception->response)->status(),
                'body' => optional($exception->response)->body(),
            ]);

            throw $exception;
        }
    }

    private function post(string $url, array $payload): array
    {
        if (!config('fusionpay.enabled')) {
            throw new RuntimeException('FusionPay est desactive. Activez FUSIONPAY_ENABLED=true.');
        }

        try {
            $response = Http::timeout((int) config('fusionpay.timeout', 20))
                ->acceptJson()
                ->withHeaders($this->headers())
                ->post($url, $payload)
                ->throw();

            return (array) $response->json();
        } catch (RequestException $exception) {
            Log::error('FusionPay request failed', [
                'url' => $url,
                'payload' => $payload,
                'status' => optional($exception->response)->status(),
                'body' => optional($exception->response)->body(),
            ]);

            throw $exception;
        }
    }

    private function headers(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ($apiKey = config('fusionpay.api_key')) {
            $headers['Authorization'] = 'Bearer ' . $apiKey;
            $headers['X-API-KEY'] = $apiKey;
        }

        return $headers;
    }

    private function resolveInitUrl(): string
    {
        if ($apiLink = config('fusionpay.api_link')) {
            return rtrim((string) $apiLink, '/');
        }

        return rtrim((string) config('fusionpay.base_url'), '/') . config('fusionpay.endpoints.init_payment');
    }

    private function resolveVerifyUrl(string $token): string
    {
        $verifyBaseUrl = (string) (config('fusionpay.verify_base_url') ?: 'https://www.pay.moneyfusion.net');
        $path = str_replace('{token}', urlencode($token), (string) config('fusionpay.endpoints.verify_payment', '/paiementNotif/{token}'));

        return rtrim($verifyBaseUrl, '/') . $path;
    }

    private function normalizeStatus($status): string
    {
        $value = strtolower(trim((string) $status));
        if (in_array($value, ['paid', 'success', 'successful', 'completed', 'payin.session.completed'], true)) {
            return 'paid';
        }
        if (in_array($value, ['failure', 'failed', 'cancelled', 'canceled', 'payin.session.cancelled', 'no paid'], true)) {
            return 'failed';
        }

        return 'pending';
    }
}
