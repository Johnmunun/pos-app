<?php

use Illuminate\Support\Facades\Route;
use Src\Infrastructure\Mobile\Http\Controllers\MobileAuthController;
use Src\Infrastructure\Mobile\Http\Controllers\MobileCommerceCustomersController;
use Src\Infrastructure\Mobile\Http\Controllers\MobileCommerceProductsController;
use Src\Infrastructure\Mobile\Http\Controllers\MobileCommerceSalesController;
use Src\Infrastructure\Mobile\Http\Controllers\MobileCommerceStockController;
use Src\Infrastructure\Mobile\Http\Controllers\MobileHardwareCustomersController;
use Src\Infrastructure\Mobile\Http\Controllers\MobileHardwareProductsController;
use Src\Infrastructure\Mobile\Http\Controllers\MobileHardwareStockController;
use Src\Infrastructure\Mobile\Http\Controllers\MobilePharmacyProductsController;
use Src\Infrastructure\Mobile\Http\Controllers\MobilePharmacyCustomersController;
use Src\Infrastructure\Mobile\Http\Controllers\MobilePharmacySalesController;
use Src\Infrastructure\Mobile\Http\Controllers\MobilePharmacyStockController;
use Src\Infrastructure\Mobile\Http\Controllers\MobilePharmacyPurchasesController;
use Src\Infrastructure\Mobile\Http\Controllers\MobileCommercePurchasesController;
use Src\Infrastructure\Mobile\Http\Controllers\MobilePharmacyTransfersController;
use Src\Infrastructure\Mobile\Http\Controllers\MobileCommerceTransfersController;
use Src\Infrastructure\Mobile\Http\Controllers\MobilePharmacyInventoriesController;
use Src\Infrastructure\Mobile\Http\Controllers\MobileCommerceInventoriesController;
use Src\Infrastructure\Pharmacy\Http\Controllers\SaleController as PharmacySaleController;
use Src\Infrastructure\Pharmacy\Http\Controllers\CustomerController as PharmacyCustomerController;
use Src\Infrastructure\Pharmacy\Http\Controllers\PurchaseController as PharmacyPurchaseController;
use Src\Infrastructure\Pharmacy\Http\Controllers\StockTransferController as PharmacyStockTransferController;
use Src\Infrastructure\Pharmacy\Http\Controllers\InventoryController as PharmacyInventoryController;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcCustomerController;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcInventoryController;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcPurchaseController;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcSaleController;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcStockTransferController;
use Src\Infrastructure\Quincaillerie\Http\Controllers\CustomerController as HardwareCustomerController;
use Src\Infrastructure\Quincaillerie\Http\Controllers\InventoryController as HardwareInventoryController;

/**
 * Mobile API (client only).
 *
 * - Auth via Sanctum token (no cookies required)
 * - Versioned under /api/v1/mobile
 * - Routes split per module (pharmacy, hardware, commerce, ...)
 */
Route::prefix('v1/mobile')->group(function () {
    // Public: login only
    Route::post('/auth/login', [MobileAuthController::class, 'login']);

    // Authenticated: token required
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [MobileAuthController::class, 'logout']);
        Route::get('/auth/me', [MobileAuthController::class, 'me']);
        Route::get('/bootstrap', [MobileAuthController::class, 'bootstrap']);
        Route::get('/bootstrap/{module}', [MobileAuthController::class, 'posBootstrap']);

        // Pharmacy module (v1 MVP)
        Route::prefix('pharmacy')->group(function () {
            // POS catalog (products + categories)
            Route::get(
                '/products',
                [MobilePharmacyProductsController::class, 'index']
            )->middleware('permission:pharmacy.pharmacy.product.manage|pharmacy.product.manage|pharmacy.product.view');

            Route::get(
                '/categories',
                [MobilePharmacyProductsController::class, 'categories']
            )->middleware('permission:pharmacy.pharmacy.product.manage|pharmacy.product.manage|pharmacy.product.view');

            // Customers (client list + create)
            Route::get(
                '/customers/active',
                [MobilePharmacyCustomersController::class, 'active']
            )->middleware('permission:pharmacy.customer.view|pharmacy.sales.manage');

            Route::post(
                '/customers',
                [PharmacyCustomerController::class, 'store']
            )->middleware('permission:pharmacy.customer.create');

            Route::post(
                '/customers/quick-create',
                [PharmacySaleController::class, 'quickCreateCustomer']
            )->middleware('permission:pharmacy.sales.manage');

            // Sales (draft/create -> lines -> finalize/cancel)
            Route::get(
                '/sales',
                [MobilePharmacySalesController::class, 'index']
            )->middleware('permission:pharmacy.sales.view|pharmacy.sales.manage');

            Route::get(
                '/sales/{id}',
                [MobilePharmacySalesController::class, 'show']
            )->middleware('permission:pharmacy.sales.view|pharmacy.sales.manage');
            Route::get(
                '/sales/{id}/receipt',
                [MobilePharmacySalesController::class, 'receipt']
            )->middleware('permission:pharmacy.sales.view|pharmacy.sales.manage');

            Route::post(
                '/sales',
                [PharmacySaleController::class, 'store']
            )->middleware('permission:pharmacy.sales.manage');

            Route::put(
                '/sales/{id}',
                [PharmacySaleController::class, 'update']
            )->middleware('permission:pharmacy.sales.manage');

            Route::post(
                '/sales/{id}/finalize',
                [PharmacySaleController::class, 'finalize']
            )->middleware('permission:pharmacy.sales.manage');

            Route::post(
                '/sales/{id}/cancel',
                [PharmacySaleController::class, 'cancel']
            )->middleware('permission:pharmacy.sales.cancel|pharmacy.sales.manage');

            // Purchases
            Route::get(
                '/purchases',
                [MobilePharmacyPurchasesController::class, 'index']
            )->middleware('permission:purchase.view|pharmacy.purchase.view');

            Route::get(
                '/purchases/{id}',
                [MobilePharmacyPurchasesController::class, 'show']
            )->middleware('permission:purchase.view|pharmacy.purchase.view');
            Route::get(
                '/purchases/{id}/receipt',
                [MobilePharmacyPurchasesController::class, 'receipt']
            )->middleware('permission:purchase.view|pharmacy.purchase.view');

            Route::post(
                '/purchases',
                [PharmacyPurchaseController::class, 'store']
            )->middleware('permission:purchase.create|pharmacy.purchase.create');

            Route::post(
                '/purchases/{id}/confirm',
                [PharmacyPurchaseController::class, 'confirm']
            )->middleware('permission:purchase.confirm|pharmacy.purchase.update');

            Route::post(
                '/purchases/{id}/receive',
                [PharmacyPurchaseController::class, 'receive']
            )->middleware('permission:purchase.receive|pharmacy.purchase.update');

            Route::post(
                '/purchases/{id}/cancel',
                [PharmacyPurchaseController::class, 'cancel']
            )->middleware('permission:purchase.cancel|pharmacy.purchase.update');

            // Transfers
            Route::get('/transfers', [MobilePharmacyTransfersController::class, 'index'])
                ->middleware('permission:stock.transfer.view|stock.transfer.manage');
            Route::get('/transfers/{id}', [MobilePharmacyTransfersController::class, 'show'])
                ->middleware('permission:stock.transfer.view|stock.transfer.manage');
            Route::post('/transfers', [PharmacyStockTransferController::class, 'store'])
                ->middleware('permission:stock.transfer.manage');
            Route::post('/transfers/{id}/items', [PharmacyStockTransferController::class, 'addItem'])
                ->middleware('permission:stock.transfer.manage');
            Route::put('/transfers/{id}/items/{itemId}', [PharmacyStockTransferController::class, 'updateItem'])
                ->middleware('permission:stock.transfer.manage');
            Route::delete('/transfers/{id}/items/{itemId}', [PharmacyStockTransferController::class, 'removeItem'])
                ->middleware('permission:stock.transfer.manage');
            Route::post('/transfers/{id}/validate', [PharmacyStockTransferController::class, 'validate'])
                ->middleware('permission:stock.transfer.manage');
            Route::post('/transfers/{id}/cancel', [PharmacyStockTransferController::class, 'cancel'])
                ->middleware('permission:stock.transfer.manage');

            // Inventories
            Route::get('/inventories', [MobilePharmacyInventoriesController::class, 'index'])
                ->middleware('permission:inventory.view');
            Route::get('/inventories/{id}', [MobilePharmacyInventoriesController::class, 'show'])
                ->middleware('permission:inventory.view');
            Route::post('/inventories', [PharmacyInventoryController::class, 'store'])
                ->middleware('permission:inventory.create');
            Route::post('/inventories/{id}/start', [PharmacyInventoryController::class, 'start'])
                ->middleware('permission:inventory.edit|inventory.create');
            Route::post('/inventories/{id}/counts', [PharmacyInventoryController::class, 'updateCounts'])
                ->middleware('permission:inventory.edit');
            Route::post('/inventories/{id}/counts/{productId}', [PharmacyInventoryController::class, 'updateSingleCount'])
                ->middleware('permission:inventory.edit');
            Route::post('/inventories/{id}/validate', [PharmacyInventoryController::class, 'validate'])
                ->middleware('permission:inventory.validate');
            Route::post('/inventories/{id}/cancel', [PharmacyInventoryController::class, 'cancel'])
                ->middleware('permission:inventory.cancel');

            // Stock
            Route::get(
                '/stock',
                [MobilePharmacyStockController::class, 'index']
            )->middleware('permission:stock.view|pharmacy.pharmacy.stock.manage');

            Route::get(
                '/stock/movements',
                [MobilePharmacyStockController::class, 'movements']
            )->middleware('permission:stock.movement.view|pharmacy.pharmacy.stock.manage');
        });

        // Hardware module (v1 parity with pharmacy core POS)
        Route::prefix('hardware')->group(function () {
            // POS catalog (products + categories)
            Route::get(
                '/products',
                [MobileHardwareProductsController::class, 'index']
            )->middleware('permission:hardware.product.view|hardware.product.manage');

            Route::get(
                '/categories',
                [MobileHardwareProductsController::class, 'categories']
            )->middleware('permission:hardware.category.view|hardware.product.manage');

            // Customers (client list + create)
            Route::get(
                '/customers/active',
                [MobileHardwareCustomersController::class, 'active']
            )->middleware('permission:hardware.customer.view|hardware.sales.manage');

            Route::post(
                '/customers',
                [HardwareCustomerController::class, 'store']
            )->middleware('permission:hardware.customer.create');

            // Sales read APIs
            Route::get(
                '/sales',
                [MobilePharmacySalesController::class, 'index']
            )->middleware('permission:hardware.sales.view|hardware.sales.manage');

            Route::get(
                '/sales/{id}',
                [MobilePharmacySalesController::class, 'show']
            )->middleware('permission:hardware.sales.view|hardware.sales.manage');
            Route::get(
                '/sales/{id}/receipt',
                [MobilePharmacySalesController::class, 'receipt']
            )->middleware('permission:hardware.sales.view|hardware.sales.manage');

            // Sales write APIs (reusing existing hardware-aware SaleController)
            Route::post(
                '/sales',
                [PharmacySaleController::class, 'store']
            )->middleware('permission:hardware.sales.manage');

            Route::put(
                '/sales/{id}',
                [PharmacySaleController::class, 'update']
            )->middleware('permission:hardware.sales.manage');

            Route::post(
                '/sales/{id}/finalize',
                [PharmacySaleController::class, 'finalize']
            )->middleware('permission:hardware.sales.manage');

            Route::post(
                '/sales/{id}/cancel',
                [PharmacySaleController::class, 'cancel']
            )->middleware('permission:hardware.sales.cancel|hardware.sales.manage');

            // Purchases
            Route::get(
                '/purchases',
                [MobilePharmacyPurchasesController::class, 'index']
            )->middleware('permission:hardware.purchase.view|purchase.view');

            Route::get(
                '/purchases/{id}',
                [MobilePharmacyPurchasesController::class, 'show']
            )->middleware('permission:hardware.purchase.view|purchase.view');
            Route::get(
                '/purchases/{id}/receipt',
                [MobilePharmacyPurchasesController::class, 'receipt']
            )->middleware('permission:hardware.purchase.view|purchase.view');

            Route::post(
                '/purchases',
                [PharmacyPurchaseController::class, 'store']
            )->middleware('permission:hardware.purchase.create|purchase.create');

            Route::post(
                '/purchases/{id}/confirm',
                [PharmacyPurchaseController::class, 'confirm']
            )->middleware('permission:hardware.purchase.update|purchase.confirm');

            Route::post(
                '/purchases/{id}/receive',
                [PharmacyPurchaseController::class, 'receive']
            )->middleware('permission:hardware.purchase.update|purchase.receive');

            Route::post(
                '/purchases/{id}/cancel',
                [PharmacyPurchaseController::class, 'cancel']
            )->middleware('permission:hardware.purchase.update|purchase.cancel');

            // Transfers
            Route::get('/transfers', [MobilePharmacyTransfersController::class, 'index'])
                ->middleware('permission:hardware.stock.transfer.view|hardware.stock.transfer.manage');
            Route::get('/transfers/{id}', [MobilePharmacyTransfersController::class, 'show'])
                ->middleware('permission:hardware.stock.transfer.view|hardware.stock.transfer.manage');
            Route::post('/transfers', [PharmacyStockTransferController::class, 'store'])
                ->middleware('permission:hardware.stock.transfer.manage');
            Route::post('/transfers/{id}/items', [PharmacyStockTransferController::class, 'addItem'])
                ->middleware('permission:hardware.stock.transfer.manage');
            Route::put('/transfers/{id}/items/{itemId}', [PharmacyStockTransferController::class, 'updateItem'])
                ->middleware('permission:hardware.stock.transfer.manage');
            Route::delete('/transfers/{id}/items/{itemId}', [PharmacyStockTransferController::class, 'removeItem'])
                ->middleware('permission:hardware.stock.transfer.manage');
            Route::post('/transfers/{id}/validate', [PharmacyStockTransferController::class, 'validate'])
                ->middleware('permission:hardware.stock.transfer.manage');
            Route::post('/transfers/{id}/cancel', [PharmacyStockTransferController::class, 'cancel'])
                ->middleware('permission:hardware.stock.transfer.manage');

            // Inventories
            Route::get('/inventories', [MobilePharmacyInventoriesController::class, 'index'])
                ->middleware('permission:inventory.view|hardware.inventory.view');
            Route::get('/inventories/{id}', [MobilePharmacyInventoriesController::class, 'show'])
                ->middleware('permission:inventory.view|hardware.inventory.view');
            Route::post('/inventories', [HardwareInventoryController::class, 'store'])
                ->middleware('permission:inventory.create|hardware.inventory.create');
            Route::post('/inventories/{id}/start', [HardwareInventoryController::class, 'start'])
                ->middleware('permission:inventory.edit|hardware.inventory.edit');
            Route::post('/inventories/{id}/counts', [HardwareInventoryController::class, 'updateCounts'])
                ->middleware('permission:inventory.edit|hardware.inventory.edit');
            Route::post('/inventories/{id}/counts/{productId}', [HardwareInventoryController::class, 'updateSingleCount'])
                ->middleware('permission:inventory.edit|hardware.inventory.edit');
            Route::post('/inventories/{id}/validate', [HardwareInventoryController::class, 'validate'])
                ->middleware('permission:inventory.validate|hardware.inventory.validate');
            Route::post('/inventories/{id}/cancel', [HardwareInventoryController::class, 'cancel'])
                ->middleware('permission:inventory.cancel|hardware.inventory.cancel');

            // Stock
            Route::get(
                '/stock',
                [MobileHardwareStockController::class, 'index']
            )->middleware('permission:hardware.stock.view|hardware.stock.manage');

            Route::get(
                '/stock/movements',
                [MobileHardwareStockController::class, 'movements']
            )->middleware('permission:hardware.stock.movement.view|hardware.stock.manage');
        });

        // Commerce module (global commerce)
        Route::prefix('commerce')->group(function () {
            // Catalog
            Route::get(
                '/products',
                [MobileCommerceProductsController::class, 'index']
            )->middleware('permission:module.commerce');

            Route::get(
                '/categories',
                [MobileCommerceProductsController::class, 'categories']
            )->middleware('permission:module.commerce');

            // Customers
            Route::get(
                '/customers/active',
                [MobileCommerceCustomersController::class, 'active']
            )->middleware('permission:module.commerce');

            Route::post(
                '/customers',
                [GcCustomerController::class, 'store']
            )->middleware('permission:module.commerce');

            Route::post(
                '/customers/quick-create',
                [GcSaleController::class, 'quickCreateCustomer']
            )->middleware('permission:module.commerce');

            // Sales read
            Route::get(
                '/sales',
                [MobileCommerceSalesController::class, 'index']
            )->middleware('permission:module.commerce');

            Route::get(
                '/sales/{id}',
                [MobileCommerceSalesController::class, 'show']
            )->middleware('permission:module.commerce');
            Route::get(
                '/sales/{id}/receipt',
                [MobileCommerceSalesController::class, 'receipt']
            )->middleware('permission:module.commerce');

            // Sales write
            Route::post(
                '/sales',
                [GcSaleController::class, 'store']
            )->middleware('permission:module.commerce');

            Route::post(
                '/sales/{id}/finalize',
                [GcSaleController::class, 'finalize']
            )->middleware('permission:module.commerce');

            // Purchases
            Route::get(
                '/purchases',
                [MobileCommercePurchasesController::class, 'index']
            )->middleware('permission:module.commerce');

            Route::get(
                '/purchases/{id}',
                [MobileCommercePurchasesController::class, 'show']
            )->middleware('permission:module.commerce');
            Route::get(
                '/purchases/{id}/receipt',
                [MobileCommercePurchasesController::class, 'receipt']
            )->middleware('permission:module.commerce');

            Route::post(
                '/purchases',
                [GcPurchaseController::class, 'store']
            )->middleware('permission:module.commerce');

            Route::post(
                '/purchases/{id}/receive',
                [GcPurchaseController::class, 'receive']
            )->middleware('permission:module.commerce');

            // Transfers
            Route::get('/transfers', [MobileCommerceTransfersController::class, 'index'])
                ->middleware('permission:module.commerce');
            Route::get('/transfers/{id}', [MobileCommerceTransfersController::class, 'show'])
                ->middleware('permission:module.commerce');
            Route::post('/transfers', [GcStockTransferController::class, 'store'])
                ->middleware('permission:module.commerce');
            Route::post('/transfers/{id}/items', [GcStockTransferController::class, 'addItem'])
                ->middleware('permission:module.commerce');
            Route::put('/transfers/{id}/items/{itemId}', [GcStockTransferController::class, 'updateItem'])
                ->middleware('permission:module.commerce');
            Route::delete('/transfers/{id}/items/{itemId}', [GcStockTransferController::class, 'removeItem'])
                ->middleware('permission:module.commerce');
            Route::post('/transfers/{id}/validate', [GcStockTransferController::class, 'validate'])
                ->middleware('permission:module.commerce');
            Route::post('/transfers/{id}/cancel', [GcStockTransferController::class, 'cancel'])
                ->middleware('permission:module.commerce');

            // Inventories
            Route::get('/inventories', [MobileCommerceInventoriesController::class, 'index'])
                ->middleware('permission:module.commerce');
            Route::get('/inventories/{id}', [MobileCommerceInventoriesController::class, 'show'])
                ->middleware('permission:module.commerce');
            Route::post('/inventories', [GcInventoryController::class, 'store'])
                ->middleware('permission:module.commerce');
            Route::post('/inventories/{id}/start', [GcInventoryController::class, 'start'])
                ->middleware('permission:module.commerce');
            Route::post('/inventories/{id}/counts', [GcInventoryController::class, 'updateCounts'])
                ->middleware('permission:module.commerce');
            Route::post('/inventories/{id}/validate', [GcInventoryController::class, 'validate'])
                ->middleware('permission:module.commerce');
            Route::post('/inventories/{id}/cancel', [GcInventoryController::class, 'cancel'])
                ->middleware('permission:module.commerce');

            // Stock
            Route::get(
                '/stock',
                [MobileCommerceStockController::class, 'index']
            )->middleware('permission:module.commerce');

            Route::get(
                '/stock/movements',
                [MobileCommerceStockController::class, 'movements']
            )->middleware('permission:module.commerce');
        });
    });
});

