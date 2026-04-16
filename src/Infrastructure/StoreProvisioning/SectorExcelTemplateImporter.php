<?php

namespace Src\Infrastructure\StoreProvisioning;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Shop;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\CategoryModel as GcCategoryModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel as GcProductModel;
use Src\Infrastructure\Pharmacy\Models\CategoryModel as PharmacyCategoryModel;
use Src\Infrastructure\Pharmacy\Models\ProductModel as PharmacyProductModel;
use Src\Infrastructure\Quincaillerie\Models\CategoryModel as HardwareCategoryModel;
use Src\Infrastructure\Quincaillerie\Models\ProductModel as HardwareProductModel;
use Src\Infrastructure\StoreProvisioning\Excel\WorkbookTableReader;

/**
 * Importe les classeurs du dossier database/data-templates/{secteur}/ pour la boutique donnée.
 * Idempotent : clés métier (category_code, code produit, sku) par shop.
 */
final class SectorExcelTemplateImporter
{
    public function __construct(
        private readonly WorkbookTableReader $reader = new WorkbookTableReader
    ) {}

    public function import(int $tenantId, Shop $shop, ?int $depotId, string $sector): void
    {
        $dir = SectorTemplateDirectory::basePath($sector);
        if (! is_dir($dir)) {
            throw new \RuntimeException('Dossier de template introuvable: '.$dir);
        }

        $currenciesPath = $dir.'/currencies.xlsx';
        $ratesPath = $dir.'/exchange_rates.xlsx';
        $categoriesPath = $dir.'/categories.xlsx';
        $productsPath = $dir.'/products.xlsx';

        foreach ([$currenciesPath, $ratesPath, $categoriesPath, $productsPath] as $p) {
            if (! is_file($p)) {
                throw new \RuntimeException('Fichier template manquant: '.$p);
            }
        }

        $this->importCurrencies($tenantId, $currenciesPath);
        $this->importExchangeRates($tenantId, $ratesPath);
        $this->importCategoriesAndProducts($tenantId, $shop, $depotId, $sector, $categoriesPath, $productsPath);
    }

    private function importCurrencies(int $tenantId, string $path): void
    {
        $rows = $this->reader->readSheet($path);
        foreach ($rows as $row) {
            $code = strtoupper(trim($row['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            Currency::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                [
                    'name' => $row['name'] ?? $code,
                    'symbol' => $row['symbol'] ?? $code,
                    'is_default' => $this->truthy($row['is_default'] ?? '0'),
                    'is_active' => $this->truthy($row['is_active'] ?? '1'),
                ]
            );
        }
        $defaults = Currency::query()->where('tenant_id', $tenantId)->where('is_default', true)->count();
        if ($defaults > 1) {
            $first = Currency::query()->where('tenant_id', $tenantId)->orderBy('id')->first();
            if ($first) {
                Currency::query()->where('tenant_id', $tenantId)->where('id', '!=', $first->id)->update(['is_default' => false]);
            }
        }
    }

    private function importExchangeRates(int $tenantId, string $path): void
    {
        $rows = $this->reader->readSheet($path);
        $effective = CarbonImmutable::today()->toDateString();
        foreach ($rows as $row) {
            $from = strtoupper(trim($row['from_currency_code'] ?? ''));
            $to = strtoupper(trim($row['to_currency_code'] ?? ''));
            if ($from === '' || $to === '') {
                continue;
            }
            $fromCur = Currency::query()->where('tenant_id', $tenantId)->where('code', $from)->first();
            $toCur = Currency::query()->where('tenant_id', $tenantId)->where('code', $to)->first();
            if (!$fromCur || !$toCur) {
                continue;
            }
            $rate = (float) str_replace(',', '.', (string) ($row['rate'] ?? '0'));
            $date = trim((string) ($row['effective_date'] ?? ''));
            if ($date !== '') {
                try {
                    $effective = CarbonImmutable::parse($date)->toDateString();
                } catch (\Throwable) {
                }
            }
            ExchangeRate::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'from_currency_id' => $fromCur->id,
                    'to_currency_id' => $toCur->id,
                    'effective_date' => $effective,
                ],
                ['rate' => $rate]
            );
        }
    }

    private function importCategoriesAndProducts(
        int $tenantId,
        Shop $shop,
        ?int $depotId,
        string $sector,
        string $categoriesPath,
        string $productsPath
    ): void {
        match (SectorTemplateDirectory::folderForSector($sector)) {
            'pharmacy' => $this->importPharmacy($shop, $categoriesPath, $productsPath),
            'hardware' => $this->importHardware($shop, $depotId, $categoriesPath, $productsPath),
            'global-commerce' => $this->importGc($shop, $categoriesPath, $productsPath, false),
            'ecommerce' => $this->importGc($shop, $categoriesPath, $productsPath, true),
            default => throw new \InvalidArgumentException('Import non implémenté'),
        };
    }

    private function importPharmacy(Shop $shop, string $categoriesPath, string $productsPath): void
    {
        $shopKey = (string) $shop->id;
        $catRows = $this->reader->readSheet($categoriesPath);
        $this->upsertCategoriesIterative(
            $catRows,
            fn (string $pcode) => PharmacyCategoryModel::query()
                ->where('shop_id', $shopKey)
                ->where('category_code', $pcode)
                ->exists(),
            function (string $code, ?string $parentCode, array $row) use ($shopKey) {
                $parentId = null;
                if ($parentCode !== null && $parentCode !== '') {
                    $parentId = PharmacyCategoryModel::query()
                        ->where('shop_id', $shopKey)
                        ->where('category_code', $parentCode)
                        ->value('id');
                }
                $model = PharmacyCategoryModel::query()->firstOrNew([
                    'shop_id' => $shopKey,
                    'category_code' => $code,
                ]);
                if (!$model->exists) {
                    $model->id = (string) Str::uuid();
                }
                $model->fill([
                    'name' => $row['name'] ?? $code,
                    'description' => $row['description'] ?? null,
                    'parent_id' => $parentId,
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                    'is_active' => true,
                ]);
                $model->save();
            }
        );

        $prodRows = $this->reader->readSheet($productsPath);
        foreach ($prodRows as $row) {
            $sku = trim((string) ($row['product_code'] ?? ''));
            if ($sku === '') {
                continue;
            }
            $catCode = trim((string) ($row['category_code'] ?? ''));
            $catId = PharmacyCategoryModel::query()
                ->where('shop_id', $shopKey)
                ->where('category_code', $catCode)
                ->value('id');
            if ($catId === null) {
                continue;
            }
            $code = $this->shortProductCode($shop->id, $sku);
            $model = PharmacyProductModel::query()->firstOrNew([
                'shop_id' => $shopKey,
                'code' => $code,
            ]);
            if (!$model->exists) {
                $model->id = (string) Str::uuid();
            }
            $model->fill([
                'name' => $row['name'] ?? $sku,
                'description' => $row['description'] ?? null,
                'type' => $row['type'] ?? 'MEDICINE',
                'dosage' => $row['dosage'] ?? null,
                'price_amount' => (float) str_replace(',', '.', (string) ($row['price_amount'] ?? '0')),
                'price_currency' => strtoupper(trim((string) ($row['price_currency'] ?? 'USD'))) ?: 'USD',
                'stock' => (int) round((float) str_replace(',', '.', (string) ($row['stock'] ?? '0'))),
                'category_id' => $catId,
                'is_active' => true,
                'requires_prescription' => $this->truthy($row['requires_prescription'] ?? '0'),
                'unit' => $row['unit'] ?? 'UNITE',
            ]);
            $model->save();
        }
    }

    private function importHardware(Shop $shop, ?int $depotId, string $categoriesPath, string $productsPath): void
    {
        $catRows = $this->reader->readSheet($categoriesPath);
        $this->upsertCategoriesIterative(
            $catRows,
            fn (string $pcode) => HardwareCategoryModel::query()
                ->where('shop_id', $shop->id)
                ->where('category_code', $pcode)
                ->exists(),
            function (string $code, ?string $parentCode, array $row) use ($shop, $depotId) {
                $parentId = null;
                if ($parentCode !== null && $parentCode !== '') {
                    $parentId = HardwareCategoryModel::query()
                        ->where('shop_id', $shop->id)
                        ->where('category_code', $parentCode)
                        ->value('id');
                }
                $model = HardwareCategoryModel::query()->firstOrNew([
                    'shop_id' => $shop->id,
                    'category_code' => $code,
                ]);
                if (!$model->exists) {
                    $model->id = (string) Str::uuid();
                }
                $model->fill([
                    'depot_id' => $depotId,
                    'name' => $row['name'] ?? $code,
                    'description' => $row['description'] ?? null,
                    'parent_id' => $parentId,
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                    'is_active' => true,
                ]);
                $model->save();
            }
        );

        $prodRows = $this->reader->readSheet($productsPath);
        foreach ($prodRows as $row) {
            $code = trim((string) (
                $row['product_code']
                ?? $row['code']
                ?? $row['sku']
                ?? ''
            ));
            if ($code === '') {
                continue;
            }
            $catCode = trim((string) (
                $row['category_code']
                ?? $row['categorie_code']
                ?? $row['category_id']
                ?? $row['categorie_id']
                ?? ''
            ));

            $catQuery = HardwareCategoryModel::query()->where('shop_id', $shop->id);
            if ($catCode !== '') {
                $catQuery->where(function ($q) use ($catCode) {
                    $q->where('category_code', $catCode)
                        ->orWhere('id', $catCode)
                        ->orWhere('name', $catCode);
                });
            }
            $catId = $catQuery->value('id');
            if ($catId === null) {
                continue;
            }
            $model = HardwareProductModel::query()->firstOrNew([
                'shop_id' => $shop->id,
                'code' => $code,
            ]);
            if (!$model->exists) {
                $model->id = (string) Str::uuid();
            }
            $model->fill([
                'depot_id' => $depotId,
                'name' => $row['name'] ?? $code,
                'description' => $row['description'] ?? null,
                'price_amount' => (float) str_replace(',', '.', (string) (
                    $row['price_amount']
                    ?? $row['price']
                    ?? $row['sale_price_amount']
                    ?? '0'
                )),
                'price_currency' => strtoupper(trim((string) (
                    $row['price_currency']
                    ?? $row['currency']
                    ?? $row['sale_price_currency']
                    ?? 'USD'
                ))) ?: 'USD',
                'stock' => (float) str_replace(',', '.', (string) ($row['stock'] ?? '0')),
                'type_unite' => strtoupper(trim((string) ($row['type_unite'] ?? 'UNITE'))) ?: 'UNITE',
                'quantite_par_unite' => (int) ($row['quantite_par_unite'] ?? 1),
                'est_divisible' => $this->truthy($row['est_divisible'] ?? '1'),
                'minimum_stock' => (float) str_replace(',', '.', (string) ($row['minimum_stock'] ?? '0')),
                'category_id' => $catId,
                'is_active' => true,
            ]);
            $model->save();
        }
    }

    private function importGc(Shop $shop, string $categoriesPath, string $productsPath, bool $ecommercePublish): void
    {
        $catRows = $this->reader->readSheet($categoriesPath);
        $this->upsertCategoriesIterative(
            $catRows,
            fn (string $pcode) => GcCategoryModel::query()
                ->where('shop_id', $shop->id)
                ->where('category_code', $pcode)
                ->exists(),
            function (string $code, ?string $parentCode, array $row) use ($shop) {
                $parentId = null;
                if ($parentCode !== null && $parentCode !== '') {
                    $parentId = GcCategoryModel::query()
                        ->where('shop_id', $shop->id)
                        ->where('category_code', $parentCode)
                        ->value('id');
                }
                $model = GcCategoryModel::query()->firstOrNew([
                    'shop_id' => $shop->id,
                    'category_code' => $code,
                ]);
                if (!$model->exists) {
                    $model->id = (string) Str::uuid();
                }
                $model->fill([
                    'name' => $row['name'] ?? $code,
                    'description' => $row['description'] ?? null,
                    'parent_id' => $parentId,
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                    'is_active' => true,
                ]);
                $model->save();
            }
        );

        $prodRows = $this->reader->readSheet($productsPath);
        foreach ($prodRows as $row) {
            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku === '') {
                continue;
            }
            $catCode = trim((string) ($row['category_code'] ?? ''));
            $catId = GcCategoryModel::query()
                ->where('shop_id', $shop->id)
                ->where('category_code', $catCode)
                ->value('id');
            if ($catId === null) {
                continue;
            }
            $model = GcProductModel::query()->firstOrNew([
                'shop_id' => $shop->id,
                'sku' => $sku,
            ]);
            if (!$model->exists) {
                $model->id = (string) Str::uuid();
            }
            $model->fill([
                'barcode' => $row['barcode'] ?? null,
                'name' => $row['name'] ?? $sku,
                'description' => $row['description'] ?? null,
                'category_id' => $catId,
                'purchase_price_amount' => (float) str_replace(',', '.', (string) ($row['purchase_price_amount'] ?? '0')),
                'purchase_price_currency' => strtoupper(trim((string) ($row['purchase_price_currency'] ?? 'USD'))) ?: 'USD',
                'sale_price_amount' => (float) str_replace(',', '.', (string) ($row['sale_price_amount'] ?? '0')),
                'sale_price_currency' => strtoupper(trim((string) ($row['sale_price_currency'] ?? 'USD'))) ?: 'USD',
                'stock' => (float) str_replace(',', '.', (string) ($row['stock'] ?? '0')),
                'minimum_stock' => (float) str_replace(',', '.', (string) ($row['minimum_stock'] ?? '0')),
                'is_weighted' => $this->truthy($row['is_weighted'] ?? '0'),
                'has_expiration' => $this->truthy($row['has_expiration'] ?? '0'),
                'is_active' => true,
                'is_published_ecommerce' => $ecommercePublish,
                'product_type' => 'physical',
            ]);
            $model->save();
        }
    }

    /**
     * @param  list<array<string, string|null>>  $catRows
     * @param  callable(string): bool  $parentExists
     * @param  callable(string, ?string, array): void  $upsertOne
     */
    private function upsertCategoriesIterative(array $catRows, callable $parentExists, callable $upsertOne): void
    {
        $pending = array_values(array_filter($catRows, fn ($r) => trim((string) ($r['category_code'] ?? '')) !== ''));
        $guard = 0;
        while ($pending !== [] && $guard < 500) {
            $guard++;
            $next = [];
            $progress = false;
            foreach ($pending as $row) {
                $code = trim((string) ($row['category_code'] ?? ''));
                $pcode = trim((string) ($row['parent_category_code'] ?? ''));
                if ($pcode === '') {
                    $upsertOne($code, null, $row);
                    $progress = true;
                } elseif ($parentExists($pcode)) {
                    $upsertOne($code, $pcode, $row);
                    $progress = true;
                } else {
                    $next[] = $row;
                }
            }
            if (!$progress) {
                throw new \RuntimeException('Impossible de résoudre la hiérarchie des catégories (parent manquant ou cycle).');
            }
            $pending = $next;
        }
    }

    private function truthy(?string $v): bool
    {
        $v = strtolower(trim((string) $v));

        return in_array($v, ['1', 'true', 'yes', 'oui', 'o'], true);
    }

    private function shortProductCode(int $shopId, string $sku): string
    {
        return strtoupper(substr(hash('sha256', $shopId.'|'.$sku), 0, 12));
    }
}
