<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;

class GlobalCurrenciesSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = null; // Global scope = tenant_id NULL

        $currencies = [
            ['code' => 'USD', 'name' => 'Dollar américain', 'symbol' => '$', 'is_default' => true],
            ['code' => 'XOF', 'name' => 'Franc CFA (BCEAO)', 'symbol' => 'FCFA', 'is_default' => false],
            ['code' => 'XAF', 'name' => 'Franc CFA (BEAC)', 'symbol' => 'FCFA', 'is_default' => false],
            ['code' => 'CDF', 'name' => 'Franc congolais', 'symbol' => 'FC', 'is_default' => false],
            ['code' => 'NGN', 'name' => 'Naira nigérian', 'symbol' => '₦', 'is_default' => false],
            ['code' => 'KES', 'name' => 'Shilling kényan', 'symbol' => 'KSh', 'is_default' => false],
            ['code' => 'ZAR', 'name' => 'Rand sud-africain', 'symbol' => 'R', 'is_default' => false],
        ];

        // Ensure only one default currency in global scope.
        Currency::query()->whereNull('tenant_id')->update(['is_default' => false]);

        $idsByCode = [];
        foreach ($currencies as $c) {
            $model = Currency::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $c['code']],
                [
                    'name' => $c['name'],
                    'symbol' => $c['symbol'],
                    'is_default' => (bool) $c['is_default'],
                    'is_active' => true,
                ]
            );
            $idsByCode[$c['code']] = (int) $model->id;
        }

        $today = now()->toDateString();

        // Rates expressed as: 1 USD = X TARGET
        $rates = [
            ['from' => 'USD', 'to' => 'XOF', 'rate' => 600.0],
            ['from' => 'USD', 'to' => 'XAF', 'rate' => 600.0],
            ['from' => 'USD', 'to' => 'CDF', 'rate' => 500.0],
            ['from' => 'USD', 'to' => 'NGN', 'rate' => 1600.0],
            ['from' => 'USD', 'to' => 'KES', 'rate' => 130.0],
            ['from' => 'USD', 'to' => 'ZAR', 'rate' => 19.0],
        ];

        foreach ($rates as $r) {
            $fromId = $idsByCode[$r['from']] ?? null;
            $toId = $idsByCode[$r['to']] ?? null;
            if (!$fromId || !$toId) {
                continue;
            }

            ExchangeRate::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'from_currency_id' => $fromId,
                    'to_currency_id' => $toId,
                    'effective_date' => $today,
                ],
                [
                    'rate' => $r['rate'],
                ]
            );
        }
    }
}

