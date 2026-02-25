<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpirationAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param array{shop_name: string, expired_count: int, expiring_soon_count: int, warning_days: int, items: array<int, array{product_name: string, batch_number: string, quantity: int, expiration_date: string, status: string}>} $summary
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
        $expired = (int) ($this->summary['expired_count'] ?? 0);
        $expiringSoon = (int) ($this->summary['expiring_soon_count'] ?? 0);
        $warningDays = (int) ($this->summary['warning_days'] ?? 30);
        $items = $this->summary['items'] ?? [];

        $message = (new MailMessage)
            ->subject('Alerte expirations – ' . $shopName)
            ->greeting('Bonjour ' . ($notifiable->name ?? $notifiable->email) . ',')
            ->line('Voici le résumé des lots en alerte pour **' . $shopName . '**.');

        if ($expired > 0) {
            $message->line('**' . $expired . '** lot(s) expiré(s).');
        }
        if ($expiringSoon > 0) {
            $message->line('**' . $expiringSoon . '** lot(s) expirant dans les ' . $warningDays . ' prochains jours.');
        }

        if ($expired === 0 && $expiringSoon === 0) {
            $message->line('Aucun lot en alerte pour le moment.');
            return $message;
        }

        if (!empty($items)) {
            $message->line('Détail des lots concernés :');
            $rows = [];
            foreach (array_slice($items, 0, 20) as $item) {
                $rows[] = sprintf(
                    '- %s (lot %s) : %s unités – Expiration %s – %s',
                    $item['product_name'] ?? '',
                    $item['batch_number'] ?? '',
                    (string) ($item['quantity'] ?? 0),
                    $item['expiration_date'] ?? '',
                    $item['status'] ?? ''
                );
            }
            $message->lines($rows);
            if (count($items) > 20) {
                $message->line('... et ' . (count($items) - 20) . ' autre(s) lot(s).');
            }
        }

        $message->action('Voir les expirations', url('/pharmacy/expirations'))
            ->line('Merci d\'utiliser notre application.');

        return $message;
    }
}
