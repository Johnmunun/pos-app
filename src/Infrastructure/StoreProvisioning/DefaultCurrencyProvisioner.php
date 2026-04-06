<?php

namespace Src\Infrastructure\StoreProvisioning;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Shop;
use Carbon\CarbonImmutable;

/**
 * Devises minimales USD / CDF / XAF (FCFA) + taux initiaux, idempotent par tenant.
 */
final class DefaultCurrencyProvisioner
{
    public function ensureForTenant(int $tenantId, ?Shop $shop = null): void
    {
        $effective = CarbonImmutable::today()->toDateString();

        $usd = Currency::query()->firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'USD'],
            [
                'name' => 'Dollar américain',
                'symbol' => '$',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        Currency::query()->where('tenant_id', $tenantId)->where('id', '!=', $usd->id)->update(['is_default' => false]);
        if (!$usd->is_default) {
            $usd->is_default = true;
            $usd->save();
        }

        $cdf = Currency::query()->firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'CDF'],
            [
                'name' => 'Franc congolais',
                'symbol' => 'FC',
                'is_default' => false,
                'is_active' => true,
            ]
        );

        $xaf = Currency::query()->firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'XAF'],
            [
                'name' => 'Franc CFA (CEMAC)',
                'symbol' => 'FCFA',
                'is_default' => false,
                'is_active' => true,
            ]
        );

        $rateUsdCdf = (float) config('store_templates.default_usd_to_cdf', 2850);
        $rateUsdXaf = (float) config('store_templates.default_usd_to_xaf', 620);

        $this->upsertRate($tenantId, $usd->id, $cdf->id, $rateUsdCdf, $effective);
        $this->upsertRate($tenantId, $usd->id, $xaf->id, $rateUsdXaf, $effective);

        if ($shop !== null) {
            $shop->currency = strtoupper((string) $usd->code);
            $shop->save();
        }
    }

    private function upsertRate(int $tenantId, int $fromId, int $toId, float $rate, string $effectiveDate): void
    {
        ExchangeRate::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'from_currency_id' => $fromId,
                'to_currency_id' => $toId,
                'effective_date' => $effectiveDate,
            ],
            ['rate' => $rate]
        );
    }
}
