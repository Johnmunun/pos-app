<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Alertes pharmacie par email (à exécuter quotidiennement via cron)
Schedule::command('pharmacy:send-expiration-alerts')->dailyAt('07:00');
Schedule::command('pharmacy:send-low-stock-alerts')->dailyAt('07:15');
