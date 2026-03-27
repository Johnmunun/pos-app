<?php

namespace Src\Infrastructure\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DynamicMailSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class AdminMailSettingsController extends Controller
{
    public function __construct(
        private DynamicMailSettingsService $mailSettingsService
    ) {
    }

    public function index(): Response
    {
        $settings = $this->mailSettingsService->getStored();

        return Inertia::render('Admin/MailSettings', [
            'mailSettings' => [
                'enabled' => (bool) ($settings['enabled'] ?? false),
                'host' => (string) ($settings['host'] ?? ''),
                'port' => (int) ($settings['port'] ?? 587),
                'encryption' => (string) ($settings['encryption'] ?? 'tls'),
                'username' => (string) ($settings['username'] ?? ''),
                'from_address' => (string) ($settings['from_address'] ?? ''),
                'from_name' => (string) ($settings['from_name'] ?? ''),
                'password_set' => !empty($settings['password_encrypted']),
                'events' => [
                    'account_activated' => (bool) (($settings['events']['account_activated'] ?? $this->mailSettingsService->defaultEvents()['account_activated']) ?? true),
                    'sale_completed' => (bool) (($settings['events']['sale_completed'] ?? $this->mailSettingsService->defaultEvents()['sale_completed']) ?? true),
                    'stock_low' => (bool) (($settings['events']['stock_low'] ?? $this->mailSettingsService->defaultEvents()['stock_low']) ?? true),
                    'stock_expiration' => (bool) (($settings['events']['stock_expiration'] ?? $this->mailSettingsService->defaultEvents()['stock_expiration']) ?? true),
                    'ecommerce_order' => (bool) (($settings['events']['ecommerce_order'] ?? $this->mailSettingsService->defaultEvents()['ecommerce_order']) ?? true),
                    'sales_monthly_limit' => (bool) (($settings['events']['sales_monthly_limit'] ?? $this->mailSettingsService->defaultEvents()['sales_monthly_limit']) ?? true),
                ],
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['nullable', 'string', 'in:none,tls,ssl'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:500'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
            'events.account_activated' => ['nullable', 'boolean'],
            'events.sale_completed' => ['nullable', 'boolean'],
            'events.stock_low' => ['nullable', 'boolean'],
            'events.stock_expiration' => ['nullable', 'boolean'],
            'events.ecommerce_order' => ['nullable', 'boolean'],
            'events.sales_monthly_limit' => ['nullable', 'boolean'],
        ]);

        $existing = $this->mailSettingsService->getStored();
        $passwordEncrypted = $existing['password_encrypted'] ?? null;
        if (!empty($data['password'])) {
            $passwordEncrypted = Crypt::encryptString((string) $data['password']);
        }

        $payload = [
            'enabled' => (bool) ($data['enabled'] ?? false),
            'host' => (string) $data['host'],
            'port' => (int) $data['port'],
            'encryption' => ($data['encryption'] ?? 'none') === 'none' ? null : (string) $data['encryption'],
            'username' => (string) $data['username'],
            'password_encrypted' => $passwordEncrypted,
            'from_address' => (string) $data['from_address'],
            'from_name' => (string) $data['from_name'],
            'events' => [
                'account_activated' => (bool) data_get($data, 'events.account_activated', true),
                'sale_completed' => (bool) data_get($data, 'events.sale_completed', true),
                'stock_low' => (bool) data_get($data, 'events.stock_low', true),
                'stock_expiration' => (bool) data_get($data, 'events.stock_expiration', true),
                'ecommerce_order' => (bool) data_get($data, 'events.ecommerce_order', true),
                'sales_monthly_limit' => (bool) data_get($data, 'events.sales_monthly_limit', true),
            ],
        ];

        $this->mailSettingsService->save($payload);

        return back()->with('success', 'Configuration mail enregistree.');
    }

    public function sendTest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'to_email' => ['required', 'email', 'max:255'],
        ]);

        try {
            $applied = $this->mailSettingsService->applyFromStorage();
            if (!$applied) {
                return back()->withErrors(['to_email' => 'Le service mail est desactive.']);
            }

            Mail::raw('Ceci est un email de test depuis la configuration SMTP OmniPOS.', function ($message) use ($validated) {
                $message->to($validated['to_email'])->subject('Test SMTP OmniPOS');
            });

            return back()->with('success', 'Email de test envoye.');
        } catch (\Throwable $e) {
            Log::warning('Mail test failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['to_email' => 'Echec envoi test: '.$e->getMessage()]);
        }
    }
}

