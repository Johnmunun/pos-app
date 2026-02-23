<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;

/**
 * Service de conversion de devises via les taux de change configurés.
 * Convertit un montant d'une devise vers une autre pour un tenant donné.
 */
class CurrencyConversionService
{
    /**
     * Convertit un montant de la devise source vers la devise cible.
     *
     * @param  float  $amount  Montant à convertir
     * @param  string  $fromCurrencyCode  Code devise source (ex: USD, CDF)
     * @param  string  $toCurrencyCode  Code devise cible
     * @param  int|string  $tenantId  ID du tenant
     * @return float|null Montant converti, ou null si pas de taux ou même devise
     */
    public function convert(float $amount, string $fromCurrencyCode, string $toCurrencyCode, int|string $tenantId): ?float
    {
        $fromCode = strtoupper($fromCurrencyCode);
        $toCode = strtoupper($toCurrencyCode);

        if ($fromCode === $toCode) {
            return $amount;
        }

        $tenantId = (int) $tenantId;
        $fromCurrency = Currency::where('tenant_id', $tenantId)->where('code', $fromCode)->first();
        $toCurrency = Currency::where('tenant_id', $tenantId)->where('code', $toCode)->first();

        if (!$fromCurrency || !$toCurrency) {
            return null;
        }

        // Taux direct: from -> to
        $rate = ExchangeRate::where('tenant_id', $tenantId)
            ->where('from_currency_id', $fromCurrency->id)
            ->where('to_currency_id', $toCurrency->id)
            ->orderByDesc('effective_date')
            ->first();

        if ($rate) {
            return round((float) $amount * (float) $rate->rate, 2);
        }

        // Taux inverse: to -> from, on divise
        $inverseRate = ExchangeRate::where('tenant_id', $tenantId)
            ->where('from_currency_id', $toCurrency->id)
            ->where('to_currency_id', $fromCurrency->id)
            ->orderByDesc('effective_date')
            ->first();

        if ($inverseRate && (float) $inverseRate->rate > 0) {
            return round((float) $amount / (float) $inverseRate->rate, 2);
        }

        return null;
    }

    /**
     * Convertit un montant vers la devise cible si possible, sinon retourne le montant original.
     *
     * @param  float  $amount  Montant à convertir
     * @param  string  $fromCurrencyCode  Code devise source
     * @param  string  $toCurrencyCode  Code devise cible
     * @param  int|string  $tenantId  ID du tenant
     * @return float Montant converti ou original
     */
    public function convertOrKeep(float $amount, string $fromCurrencyCode, string $toCurrencyCode, int|string $tenantId): float
    {
        $converted = $this->convert($amount, $fromCurrencyCode, $toCurrencyCode, $tenantId);

        return $converted !== null ? $converted : $amount;
    }
}
