<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ExpirationAlertMailService;
use Illuminate\Console\Command;

class SendExpirationAlertEmails extends Command
{
    /**
     * @var string
     */
    protected $signature = 'pharmacy:send-expiration-alerts';

    /**
     * @var string
     */
    protected $description = 'Envoie les alertes d\'expiration des lots par email aux utilisateurs concernés';

    public function handle(ExpirationAlertMailService $service): int
    {
        $this->info('Envoi des alertes expiration...');
        $sent = $service->sendExpirationAlerts();
        $this->info("{$sent} notification(s) d'expiration envoyée(s).");
        return self::SUCCESS;
    }
}
