<?php

namespace Src\Application\Currency\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Shop;
use Illuminate\Http\Request;

/**
 * Devise affichée + carte de taux (référence = devise par défaut du tenant).
 * Session unifiée pour vitrine e-commerce, panier admin et POS Commerce.
 */
final class TenantDisplayCurrencyService
{
    private const SESSION_KEY = 'display_currency_';

    private const LEGACY_STOREFRONT_SESSION_KEY = 'storefront_display_currency_';

    /**
     * @return array{
     *   currency: string,
     *   exchange_rates: array<string, float>,
     *   available_currencies: array<int, array{code: string, name?: string, symbol?: string, is_default?: bool}>,
     *   default_currency: string
     * }
     */
    public function resolve(Request $request, string $tenantId, ?Shop $shop = null, bool $allowQueryOverride = true): array
    {
        $config = $this->buildRateMap($tenantId, $shop?->currency);
        $activeCodes = Currency::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['code', 'name', 'symbol', 'is_default']);

        $activeCodeList = $activeCodes->pluck('code')
            ->map(fn ($c) => strtoupper((string) $c))
            ->values()
            ->all();

        if ($allowQueryOverride) {
            $requested = strtoupper(trim((string) $request->query('currency', '')));
            if ($requested !== '' && in_array($requested, $activeCodeList, true)) {
                $this->writeSessionCurrency($request, $tenantId, $requested);
            }
        }

        $fromSession = $this->readSessionCurrency($request, $tenantId, $activeCodeList);
        $display = ($fromSession !== null && in_array($fromSession, $activeCodeList, true))
            ? $fromSession
            : $config['currency'];

        $rates = $config['exchange_rates'];
        if (!isset($rates[$display])) {
            $display = $config['currency'];
            $this->forgetSessionCurrency($request, $tenantId);
        }

        $available = $activeCodes->map(fn ($c) => [
            'code' => strtoupper((string) $c->code),
            'name' => $c->name,
            'symbol' => $c->symbol ?? $c->code,
            'is_default' => (bool) $c->is_default,
        ])->values()->all();

        return [
            'currency' => $display,
            'exchange_rates' => $rates,
            'available_currencies' => $available,
            'default_currency' => $config['currency'],
        ];
    }

    /**
     * @return array{currency: string, exchange_rates: array<string, float>}
     */
    public function buildRateMap(string $tenantId, ?string $fallbackShopCurrency = null): array
    {
        $currenciesList = Currency::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->get();
        $defaultCurrencyModel = $currenciesList->firstWhere('is_default', true) ?? $currenciesList->first();
        $fallbackCode = strtoupper((string) ($fallbackShopCurrency ?: 'USD'));
        $defaultCode = $defaultCurrencyModel ? strtoupper($defaultCurrencyModel->code) : $fallbackCode;

        $exchangeRatesMap = [$defaultCode => 1.0];
        if ($defaultCurrencyModel) {
            foreach ($currenciesList as $c) {
                $code = strtoupper($c->code);
                if ($code === $defaultCode) {
                    continue;
                }
                $fromDefault = ExchangeRate::where('tenant_id', $tenantId)
                    ->where('from_currency_id', $defaultCurrencyModel->id)
                    ->where('to_currency_id', $c->id)
                    ->orderByDesc('effective_date')
                    ->first();
                if ($fromDefault && (float) $fromDefault->rate > 0) {
                    $exchangeRatesMap[$code] = (float) $fromDefault->rate;
                } else {
                    $toDefault = ExchangeRate::where('tenant_id', $tenantId)
                        ->where('from_currency_id', $c->id)
                        ->where('to_currency_id', $defaultCurrencyModel->id)
                        ->orderByDesc('effective_date')
                        ->first();
                    if ($toDefault && (float) $toDefault->rate > 0) {
                        $exchangeRatesMap[$code] = 1.0 / (float) $toDefault->rate;
                    } else {
                        $exchangeRatesMap[$code] = 1.0;
                    }
                }
            }
        }

        return ['currency' => $defaultCode, 'exchange_rates' => $exchangeRatesMap];
    }

    /**
     * @param  list<string>  $activeCodes
     */
    private function readSessionCurrency(Request $request, string $tenantId, array $activeCodes): ?string
    {
        foreach ([self::SESSION_KEY.$tenantId, self::LEGACY_STOREFRONT_SESSION_KEY.$tenantId] as $key) {
            $fromSession = $request->session()->get($key);
            if (!is_string($fromSession)) {
                continue;
            }
            $picked = strtoupper(trim($fromSession));
            if ($picked !== '' && in_array($picked, $activeCodes, true)) {
                return $picked;
            }
        }

        return null;
    }

    /**
     * Mémorise la devise choisie (POS Commerce, vitrine, etc.) pour les pages suivantes.
     */
    public function remember(Request $request, string $tenantId, string $code): void
    {
        $activeCodes = Currency::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->pluck('code')
            ->map(fn ($c) => strtoupper((string) $c))
            ->values()
            ->all();
        $upper = strtoupper(trim($code));
        if ($upper === '' || !in_array($upper, $activeCodes, true)) {
            return;
        }
        $this->writeSessionCurrency($request, $tenantId, $upper);
    }

    private function writeSessionCurrency(Request $request, string $tenantId, string $code): void
    {
        $upper = strtoupper($code);
        $request->session()->put(self::SESSION_KEY.$tenantId, $upper);
        $request->session()->put(self::LEGACY_STOREFRONT_SESSION_KEY.$tenantId, $upper);
    }

    private function forgetSessionCurrency(Request $request, string $tenantId): void
    {
        $request->session()->forget(self::SESSION_KEY.$tenantId);
        $request->session()->forget(self::LEGACY_STOREFRONT_SESSION_KEY.$tenantId);
    }
}
