<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param array{shop_name: string, items: array<int, array{product_name: string, code: string, current_stock: int, minimum_stock: int}>} $summary
     */
    public function __construct(
        private readonly array $summary
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $shopName = $this->summary['shop_name'] ?? 'Pharmacie';
        $items = $this->summary['items'] ?? [];

        $message = (new MailMessage)
            ->subject('Alerte stock faible – ' . $shopName)
            ->greeting('Bonjour ' . ($notifiable->name ?? $notifiable->email) . ',')
            ->line('Les produits suivants ont un stock en dessous du minimum pour **' . $shopName . '** :');

        if (empty($items)) {
            $message->line('Aucun produit en alerte pour le moment.');
            return $message;
        }

        $rows = [];
        foreach (array_slice($items, 0, 25) as $item) {
            $rows[] = sprintf(
                '- %s (code %s) : stock actuel **%s**, minimum **%s**',
                $item['product_name'] ?? '',
                $item['code'] ?? '',
                (string) ($item['current_stock'] ?? 0),
                (string) ($item['minimum_stock'] ?? 0)
            );
        }
        $message->lines($rows);
        if (count($items) > 25) {
            $message->line('... et ' . (count($items) - 25) . ' autre(s) produit(s).');
        }

        $message->action('Voir le stock', url('/pharmacy/stock'))
            ->line('Merci d\'utiliser notre application.');

        return $message;
    }
}
