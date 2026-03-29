<?php

namespace Src\Infrastructure\StoreProvisioning;

use App\Models\Depot;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Infrastructure\Settings\Models\StoreSettingsModel;

/**
 * Crée le dépôt, la boutique principale et les paramètres magasin de base.
 */
final class TenantPhysicalStoreBootstrap
{
    public function __construct(
        private readonly string $shopCodeSuffix = '-SHOP-1'
    ) {}

    /**
     * @return array{shop: Shop, depot: Depot}
     */
    public function ensureDepotShopAndSettings(Tenant $tenant, User $adminUser): array
    {
        $depot = Depot::query()->where('tenant_id', $tenant->id)->first();
        if (!$depot) {
            $code = ($tenant->code ?? 'T'.$tenant->id).'-DEPOT-1';
            $depot = Depot::create([
                'tenant_id' => $tenant->id,
                'name' => ($tenant->name ?? 'Entreprise').' — Dépôt principal',
                'code' => $code,
                'is_active' => true,
            ]);
        }

        $shop = Shop::query()->where('tenant_id', $tenant->id)->where('is_active', true)->first();
        if (!$shop) {
            $shopCode = $this->buildUniqueShopCode((string) $tenant->id, (string) ($tenant->code ?? ''));
            $shop = Shop::create([
                'tenant_id' => $tenant->id,
                'depot_id' => $depot->id,
                'name' => ($tenant->name ?? 'Ma boutique').' — Point de vente principal',
                'code' => $shopCode,
                'type' => 'physical',
                'address' => $tenant->address,
                'phone' => $tenant->phone,
                'email' => $tenant->email,
                'currency' => 'USD',
                'default_tax_rate' => 0,
                'is_active' => true,
            ]);
        } elseif ($shop->depot_id === null) {
            $shop->depot_id = $depot->id;
            $shop->save();
        }

            if (Schema::hasColumn('users', 'shop_id')) {
                $sid = (string) $shop->id;
                if ($adminUser->shop_id === null || (string) $adminUser->shop_id !== $sid) {
                    $adminUser->shop_id = $sid;
                    $adminUser->save();
                }
            }

        $this->ensureStoreSettingsRow($shop, $tenant);

        return ['shop' => $shop, 'depot' => $depot];
    }

    private function ensureStoreSettingsRow(Shop $shop, Tenant $tenant): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('store_settings')) {
            return;
        }

        $exists = StoreSettingsModel::query()->where('shop_id', $shop->id)->exists();
        if ($exists) {
            return;
        }

        StoreSettingsModel::create([
            'id' => (string) Str::uuid(),
            'shop_id' => $shop->id,
            'company_name' => $tenant->name ?? $shop->name,
            'street' => $tenant->address,
            'phone' => $tenant->phone,
            'email' => $tenant->email,
            'currency' => 'USD',
            'country' => $shop->country ?? 'CM',
        ]);
    }

    private function buildUniqueShopCode(string $tenantId, string $tenantCode): string
    {
        $base = strtoupper(Str::slug($tenantCode !== '' ? $tenantCode : 'T'.$tenantId, ''));
        $base = substr($base !== '' ? $base : 'SHOP', 0, 20);
        $code = $base.$this->shopCodeSuffix;
        $n = 1;
        while (Shop::query()->where('code', $code)->exists()) {
            $code = $base.'-'.$n.$this->shopCodeSuffix;
            $n++;
        }

        return $code;
    }
}
