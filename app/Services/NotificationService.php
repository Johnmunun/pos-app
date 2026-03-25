<?php

namespace App\Services;

use App\Models\NotificationToken;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        private readonly FcmClient $fcmClient
    ) {
    }

    public function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        $tokens = NotificationToken::query()
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->pluck('token')
            ->toArray();

        foreach ($tokens as $token) {
            $this->sendToToken($token, $title, $body, $data);
        }
    }

    public function sendToTenant(int $tenantId, string $title, string $body, array $data = []): void
    {
        $tokens = NotificationToken::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('revoked_at')
            ->pluck('token')
            ->toArray();

        foreach ($tokens as $token) {
            $this->sendToToken($token, $title, $body, $data);
        }
    }

    private function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        try {
            $this->fcmClient->send([
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => array_map(static fn ($v) => is_scalar($v) ? (string) $v : json_encode($v), $data),
            ]);
        } catch (\Throwable $e) {
            Log::warning('FCM send failed', [
                'error' => $e->getMessage(),
            ]);

            // If token invalid → revoke it
            if (str_contains($e->getMessage(), 'UNREGISTERED') || str_contains($e->getMessage(), 'registration-token-not-registered')) {
                NotificationToken::query()
                    ->where('token', $token)
                    ->update(['revoked_at' => now()]);
            }
        }
    }
}

