<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FailedLoginAttemptNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $ipAddress,
        private readonly string $userAgent,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Alerte de sécurité: tentative de connexion échouée')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Une tentative de connexion à votre compte a échoué.')
            ->line('Adresse IP: '.$this->ipAddress)
            ->line('Appareil/Navigateur: '.$this->userAgent)
            ->line('Si ce n’était pas vous, nous vous recommandons de réinitialiser votre mot de passe.');
    }
}
