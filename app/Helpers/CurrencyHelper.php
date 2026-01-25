<?php

namespace App\Helpers;

use App\Models\Currency;
use App\Models\ExchangeRate;

class CurrencyHelper
{
    /**
     * Get available currencies
     */
    public static function getCurrencies(?int $tenantId): array
    {
        if ($tenantId === null) {
            return [];
        }

        return Currency::forTenant($tenantId)
            ->active()
            ->get()
            ->map(fn($currency) => [
                'id' => $currency->id,
                'code' => $currency->code,
                'name' => $currency->name,
                'symbol' => $currency->symbol,
                'is_default' => $currency->is_default,
            ])
            ->toArray();
    }

    /**
     * Get currency symbol
     */
    public static function getSymbol(string $code): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'CDF' => 'FC',
            'XAF' => 'FCFA',
        ];

        return $symbols[$code] ?? $code;
    }

    /**
     * Format amount with currency
     */
    public static function formatAmount(float $amount, string $currency): string
    {
        $symbol = self::getSymbol($currency);
        return $symbol . ' ' . number_format($amount, 2, '.', ' ');
    }

    /**
     * Convert amount between currencies
     */
    public static function convertAmount(float $amount, string $fromCurrency, string $toCurrency, int $tenantId): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $rate = ExchangeRate::where('tenant_id', $tenantId)
            ->whereHas('fromCurrency', fn($q) => $q->where('code', $fromCurrency))
            ->whereHas('toCurrency', fn($q) => $q->where('code', $toCurrency))
            ->latest('effective_date')
            ->first();

        if (!$rate) {
            return $amount; // No conversion available
        }

        return $amount * (float) $rate->rate;
    }

    /**
     * Get product currency
     */
    public static function getProductCurrency(?string $currency): string
    {
        return $currency ?? 'USD';
    }
}


