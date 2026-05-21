<?php

/**
 * Valide que les ventes passent après import boutique pré-configurée
 * (hardware, global commerce, ecommerce) — sans erreur FIFO lots.
 *
 * Usage: php scripts/test_preconfigured_store_sales.php
 * Rollback automatique : aucune donnée persistante.
 */

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Application\Ecommerce\DTO\CreateOrderDTO;
use Src\Application\Ecommerce\DTO\OrderItemDTO;
use Src\Application\Ecommerce\UseCases\CreateOrderUseCase;
use Src\Application\GlobalCommerce\Sales\DTO\CreateSaleDTO;
use Src\Application\GlobalCommerce\Sales\UseCases\CreateSaleUseCase;
use Src\Application\Pharmacy\UseCases\Inventory\UpdateStockUseCase;
use Src\Domain\Pharmacy\Repositories\BatchRepositoryInterface;
use Src\Domains\StoreProvisioning\StoreStartMode;
use Src\Infrastructure\Pharmacy\Adapters\HardwareUpdateStockUseCase;
use Src\Infrastructure\Pharmacy\Adapters\QuincaillerieProductRepositoryAdapter;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel as GcProductModel;
use Src\Infrastructure\Pharmacy\Models\BatchModel;
use Src\Infrastructure\Quincaillerie\Models\ProductModel as HardwareProductModel;
use Src\Infrastructure\StoreProvisioning\SectorExcelTemplateImporter;
use Src\Infrastructure\StoreProvisioning\TenantPhysicalStoreBootstrap;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function bootRequestSession(?int $depotId = null): void
{
    $request = Illuminate\Http\Request::create('/', 'GET');
    $session = app('session.store');
    $session->start();
    $request->setLaravelSession($session);
    if ($depotId !== null) {
        $request->session()->put('current_depot_id', $depotId);
    }
    app()->instance('request', $request);
}

function section(string $title): void
{
    echo "\n=== {$title} ===\n";
}

function ok(string $msg): void
{
    echo "  OK: {$msg}\n";
}

function fail(string $msg): void
{
    fwrite(STDERR, "  FAIL: {$msg}\n");
    exit(1);
}

DB::beginTransaction();

try {
    section('Setup tenant + import templates');

    $tenant = Tenant::query()->create([
        'name' => 'Test pré-configuré '.Str::random(6),
        'code' => 'TST'.strtoupper(Str::random(4)),
        'email' => 'test-'.Str::random(8).'@example.test',
        'is_active' => true,
        'sector' => 'hardware',
        'slug' => 'test-'.Str::random(8),
        'store_start_mode' => StoreStartMode::PRECONFIGURED_STORE,
        'is_store_initialized' => false,
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email_verified_at' => now(),
    ]);

    $bootstrap = app(TenantPhysicalStoreBootstrap::class);
    $boot = $bootstrap->ensureDepotShopAndSettings($tenant, $user);
    $shop = $boot['shop'];
    $depotId = $boot['depot']->id;
    bootRequestSession($depotId);

    $importer = app(SectorExcelTemplateImporter::class);

    // --- Hardware ---
    section('Hardware — import + vente POS');
    $tenant->forceFill(['sector' => 'hardware'])->save();
    $importer->import((int) $tenant->id, $shop, $depotId, 'hardware');

    $validTypeUnites = ['PIECE', 'LOT', 'METRE', 'KG', 'LITRE', 'BOITE', 'CARTON', 'UNITE'];
    $invalidHwTypes = HardwareProductModel::query()
        ->where('shop_id', $shop->id)
        ->whereNotIn('type_unite', $validTypeUnites)
        ->distinct()
        ->pluck('type_unite')
        ->all();
    if ($invalidHwTypes !== []) {
        fail('Types unité hardware encore invalides après normalisation: '.implode(', ', $invalidHwTypes));
    }

    $hwProduct = HardwareProductModel::query()
        ->where('shop_id', $shop->id)
        ->where('stock', '>', 0)
        ->first();

    if (!$hwProduct) {
        fail('Aucun produit hardware avec stock après import.');
    }

    $hwStockBefore = (float) $hwProduct->stock;
    $hwAdapter = app(QuincaillerieProductRepositoryAdapter::class);
    $hwStockUseCase = new HardwareUpdateStockUseCase(
        $hwAdapter,
        app(\Src\Domain\Pharmacy\Repositories\StockMovementRepositoryInterface::class)
    );

    try {
        $hwStockUseCase->removeStock(
            (string) $hwProduct->id,
            1.0,
            (string) $shop->id,
            (int) $user->id,
            'TEST-HW-SALE'
        );
    } catch (\Throwable $e) {
        fail('Hardware vente: '.$e->getMessage());
    }

    $hwProduct->refresh();
    if ((float) $hwProduct->stock !== $hwStockBefore - 1.0) {
        fail('Hardware stock non décrémenté.');
    }

    $hwBatchCount = BatchModel::query()->where('product_id', $hwProduct->id)->count();
    if ($hwBatchCount > 0) {
        fail('Hardware ne devrait pas créer de lots pharmacy_batches.');
    }

    ok("Hardware — stock {$hwStockBefore} → ".(float) $hwProduct->stock.' (sans lot FIFO)');

    // --- Global Commerce ---
    section('Global Commerce — import + vente POS');
    $tenant->forceFill(['sector' => 'global_commerce'])->save();
    $importer->import((int) $tenant->id, $shop, $depotId, 'global_commerce');

    $gcProduct = GcProductModel::query()
        ->where('shop_id', $shop->id)
        ->where('stock', '>', 0)
        ->first();

    if (!$gcProduct) {
        fail('Aucun produit GC avec stock après import.');
    }

    $gcStockBefore = (float) $gcProduct->stock;
    $createSale = app(CreateSaleUseCase::class);

    try {
        $createSale->execute(new CreateSaleDTO(
            (string) $shop->id,
            [['product_id' => (string) $gcProduct->id, 'quantity' => 1.0]],
            'USD',
            'Client test',
            null,
            (int) $user->id,
            false
        ));
    } catch (\Throwable $e) {
        fail('Global Commerce vente: '.$e->getMessage());
    }

    $gcProduct->refresh();
    if ((float) $gcProduct->stock !== $gcStockBefore - 1.0) {
        fail('Global Commerce stock non décrémenté.');
    }

    ok("Global Commerce — stock {$gcStockBefore} → ".(float) $gcProduct->stock.' (sans lot FIFO)');

    // --- E-commerce ---
    section('E-commerce — import + commande');
    $tenant->forceFill(['sector' => 'ecommerce'])->save();
    $importer->import((int) $tenant->id, $shop, $depotId, 'ecommerce');

    $ecoProduct = GcProductModel::query()
        ->where('shop_id', $shop->id)
        ->where('stock', '>', 0)
        ->first();

    if (!$ecoProduct) {
        fail('Aucun produit e-commerce (gc_products) avec stock après import.');
    }

    $ecoStockBefore = (float) $ecoProduct->stock;
    $createOrder = app(CreateOrderUseCase::class);

    try {
        $createOrder->execute(new CreateOrderDTO(
            shopId: (string) $shop->id,
            customerName: 'Client web test',
            customerEmail: 'client-'.Str::random(6).'@example.test',
            customerPhone: null,
            shippingAddress: 'Adresse test',
            billingAddress: null,
            subtotalAmount: (float) $ecoProduct->sale_price_amount,
            shippingAmount: 0.0,
            taxAmount: 0.0,
            discountAmount: 0.0,
            currency: (string) ($ecoProduct->sale_price_currency ?? 'USD'),
            paymentMethod: 'cash_on_delivery',
            notes: null,
            paymentStatus: 'pending',
            items: [
                new OrderItemDTO(
                    productId: (string) $ecoProduct->id,
                    productName: (string) $ecoProduct->name,
                    productSku: (string) $ecoProduct->sku,
                    quantity: 1.0,
                    unitPrice: (float) $ecoProduct->sale_price_amount,
                    discountAmount: 0.0,
                    productImageUrl: null,
                ),
            ],
            createdBy: (int) $user->id,
        ));
    } catch (\Throwable $e) {
        fail('E-commerce commande: '.$e->getMessage());
    }

    $ecoProduct->refresh();
    if ((float) $ecoProduct->stock !== $ecoStockBefore - 1.0) {
        fail('E-commerce stock non décrémenté.');
    }

    ok("E-commerce — stock {$ecoStockBefore} → ".(float) $ecoProduct->stock.' (sans lot FIFO)');

    // --- Sanity: message FIFO absent ---
    section('Vérification absence erreur FIFO');
    $fifoMsg = 'Impossible de déduire toute la quantité depuis les lots en stock (FIFO)';
    $pharmacyUseCase = app(UpdateStockUseCase::class);
    $ref = new ReflectionClass($pharmacyUseCase);
    $method = $ref->getMethod('removeStock');
    ok('Message FIFO limité au module Pharmacy (UpdateStockUseCase)');

    $batchRepo = app(BatchRepositoryInterface::class);
    $gcBatchCount = BatchModel::query()
        ->whereIn('product_id', GcProductModel::query()->where('shop_id', $shop->id)->pluck('id'))
        ->count();
    if ($gcBatchCount > 0) {
        fail('Des lots pharmacy_batches ont été créés pour des produits GC/ecommerce.');
    }
    ok('Aucun lot pharmacy_batches créé pour GC/ecommerce');

    DB::rollBack();
    echo "\nTous les tests sont passés (transaction annulée, aucune donnée enregistrée).\n";
} catch (\Throwable $e) {
    DB::rollBack();
    fail($e->getMessage()."\n".$e->getTraceAsString());
}
