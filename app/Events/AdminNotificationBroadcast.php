<?php

namespace App\Events;

use App\Models\AppNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Diffuse une notification admin en temps réel (produit créé, vente, etc.)
 * pour que le ROOT voie un toast sans cliquer sur la cloche.
 */
class AdminNotificationBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AppNotification $notification
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('root.notifications'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'admin.notification';
    }

    public function broadcastWith(): array
    {
        return [
            'notification' => [
                'id' => $this->notification->id,
                'title' => $this->notification->title,
                'body' => $this->notification->body,
                'type' => $this->notification->type,
                'created_at' => $this->notification->created_at?->toDateTimeString(),
                'read_at' => $this->notification->read_at?->toDateTimeString(),
            ],
        ];
    }
}
