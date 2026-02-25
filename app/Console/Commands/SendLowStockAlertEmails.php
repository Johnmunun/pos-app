<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LowStockAlertMailService;
use Illuminate\Console\Command;

class SendLowStockAlertEmails extends Command
{
    /**
     * @var string
     */
    protected $signature = 'pharmacy:send-low-stock-alerts';

    /**
     * @var string
     */
    protected $description = 'Envoie les alertes de stock faible par email aux utilisateurs concernés';

    public function handle(LowStockAlertMailService $service): int
    {
        $this->info('Envoi des alertes stock faible...');
        $sent = $service->sendLowStockAlerts();
        $this->info("{$sent} notification(s) de stock faible envoyée(s).");
        return self::SUCCESS;
    }
}
