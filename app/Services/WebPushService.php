<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;
use App\Models\WebPushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    public function sendToAdmins(AppNotification $notification): void
    {
        $publicKey = config('services.webpush.vapid.public_key');
        $privateKey = config('services.webpush.vapid.private_key');
        $subject = config('services.webpush.vapid.subject', 'mailto:admin@example.com');

        if (!$publicKey || !$privateKey) {
            return;
        }

        $users = User::query()
            ->where('type', 'ROOT')
            ->orWhereHas('roles', function ($q) {
                $q->where('name', 'like', '%Admin%');
            })
            ->pluck('id')
            ->toArray();

        if (empty($users)) {
            return;
        }

        $subscriptions = WebPushSubscription::whereIn('user_id', $users)->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);

        $payload = json_encode([
            'title' => $notification->title,
            'body' => $notification->body,
            'type' => $notification->type,
            'created_at' => $notification->created_at?->toDateTimeString(),
        ], JSON_THROW_ON_ERROR);

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->public_key,
                'authToken' => $sub->auth_token,
                'contentEncoding' => $sub->content_encoding ?: 'aesgcm',
            ]);

            $webPush->sendOneNotification($subscription, $payload);
        }
    }

    /**
     * Envoyer une notification push à un utilisateur précis (ex. quand son compte est activé).
     */
    public function sendToUser(User $user, AppNotification $notification): void
    {
        $publicKey = config('services.webpush.vapid.public_key');
        $privateKey = config('services.webpush.vapid.private_key');
        $subject = config('services.webpush.vapid.subject', 'mailto:admin@example.com');

        if (!$publicKey || !$privateKey) {
            return;
        }

        $subscriptions = WebPushSubscription::where('user_id', $user->id)->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);

        $payload = json_encode([
            'title' => $notification->title,
            'body' => $notification->body,
            'type' => $notification->type,
            'created_at' => $notification->created_at?->toDateTimeString(),
        ], JSON_THROW_ON_ERROR);

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->public_key,
                'authToken' => $sub->auth_token,
                'contentEncoding' => $sub->content_encoding ?: 'aesgcm',
            ]);

            $webPush->sendOneNotification($subscription, $payload);
        }
    }
}

