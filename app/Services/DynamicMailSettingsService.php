<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

class DynamicMailSettingsService
{
    private const MAIL_SETTING_KEY = 'mail.smtp';
    private const DEFAULT_EVENT_SETTINGS = [
        'account_activated' => true,
        'sale_completed' => true,
        'stock_low' => true,
        'stock_expiration' => true,
        'ecommerce_order' => true,
        'sales_monthly_limit' => true,
    ];

    public function getStored(): array
    {
        $raw = (string) (AppSetting::query()->where('key', self::MAIL_SETTING_KEY)->value('value') ?? '');
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function save(array $payload): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => self::MAIL_SETTING_KEY],
            ['value' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );
    }

    public function applyFromStorage(): bool
    {
        $settings = $this->getStored();
        if (!($settings['enabled'] ?? false)) {
            return false;
        }

        $password = '';
        if (!empty($settings['password_encrypted'])) {
            try {
                $password = Crypt::decryptString((string) $settings['password_encrypted']);
            } catch (\Throwable $e) {
                $password = '';
            }
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', (string) ($settings['host'] ?? ''));
        Config::set('mail.mailers.smtp.port', (int) ($settings['port'] ?? 587));
        Config::set('mail.mailers.smtp.encryption', $settings['encryption'] ?: null);
        Config::set('mail.mailers.smtp.username', (string) ($settings['username'] ?? ''));
        Config::set('mail.mailers.smtp.password', $password);
        Config::set('mail.from.address', (string) ($settings['from_address'] ?? config('mail.from.address')));
        Config::set('mail.from.name', (string) ($settings['from_name'] ?? config('mail.from.name')));

        try {
            Mail::purge('smtp');
        } catch (\Throwable $e) {
            // noop
        }

        return true;
    }

    public function eventEnabled(string $eventKey): bool
    {
        $settings = $this->getStored();
        $events = $settings['events'] ?? [];
        $defaults = self::DEFAULT_EVENT_SETTINGS;

        if (array_key_exists($eventKey, $events)) {
            return (bool) $events[$eventKey];
        }

        return (bool) ($defaults[$eventKey] ?? true);
    }

    public function defaultEvents(): array
    {
        return self::DEFAULT_EVENT_SETTINGS;
    }
}

