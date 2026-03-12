<?php

namespace App\Events;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegisteredNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public AppNotification $notification;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user)
    {
        $this->notification = AppNotification::create([
            'user_id' => null,
            'tenant_id' => $user->tenant_id,
            'type' => 'onboarding.registered',
            'title' => 'Nouvelle inscription',
            'body' => sprintf('Un nouvel utilisateur %s (%s) vient de s’inscrire.', $user->name ?? $user->email, $user->email),
            'data' => [
                'user_id' => $user->id,
                'email' => $user->email,
                'tenant_id' => $user->tenant_id,
                'created_at' => $user->created_at?->toDateTimeString(),
            ],
        ]);

        // Optionnel : envoyer aussi une notification Web Push aux admins / ROOT
        try {
            if (class_exists(\App\Services\WebPushService::class)) {
                app(\App\Services\WebPushService::class)->sendToAdmins($this->notification);
            }
        } catch (\Throwable $e) {
            // Ne pas casser l'inscription si le push échoue
        }
    }

    public function broadcastOn(): array
    {
        // Canal global pour ROOT / admins (sera écouté par le front via Echo + laravel-websockets)
        return [
            new PrivateChannel('root.notifications'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.registered';
    }
}

