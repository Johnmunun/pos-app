<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FcmClient
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private function resolveServiceAccount(): array
    {
        $json = (string) (config('fcm.service_account_json') ?? '');
        $path = (string) (config('fcm.service_account_path') ?? '');

        if ($json !== '') {
            $decoded = json_decode($json, true);
            if (!is_array($decoded)) {
                throw new RuntimeException('FCM service account JSON invalide (FCM_SERVICE_ACCOUNT_JSON).');
            }
            return $decoded;
        }

        if ($path !== '' && is_file($path)) {
            $raw = file_get_contents($path);
            $decoded = $raw ? json_decode($raw, true) : null;
            if (!is_array($decoded)) {
                throw new RuntimeException('FCM service account file invalide (FCM_SERVICE_ACCOUNT_PATH).');
            }
            return $decoded;
        }

        throw new RuntimeException('FCM service account manquant (FCM_SERVICE_ACCOUNT_JSON ou FCM_SERVICE_ACCOUNT_PATH).');
    }

    private function accessToken(): string
    {
        $creds = new ServiceAccountCredentials(self::SCOPE, $this->resolveServiceAccount());
        $token = $creds->fetchAuthToken();
        $access = is_array($token) ? ($token['access_token'] ?? null) : null;
        if (!is_string($access) || $access === '') {
            throw new RuntimeException('Impossible de générer le token OAuth2 pour FCM.');
        }
        return $access;
    }

    public function send(array $message): array
    {
        if (!config('fcm.enabled')) {
            throw new RuntimeException('FCM désactivé (FCM_ENABLED=false).');
        }

        $projectId = (string) (config('fcm.project_id') ?: config('fcm.web.project_id'));
        if ($projectId === '') {
            throw new RuntimeException('FCM project_id manquant (FCM_PROJECT_ID).');
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $accessToken = $this->accessToken();

        $response = Http::timeout(20)
            ->acceptJson()
            ->withToken($accessToken)
            ->post($url, [
                'message' => $message,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('FCM send failed: ' . $response->status() . ' ' . (string) $response->body());
        }

        return (array) $response->json();
    }
}

