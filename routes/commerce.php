<?php

use Illuminate\Support\Facades\Route;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcProductController;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcCategoryController;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcSaleController;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcReportController;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcSupplierController;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcPurchaseController;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcDashboardController;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcCustomerController;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcExportController;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcProductMovementController;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcStockController;

/**
 * Module GlobalCommerce - Produits et catégories génériques (multi-secteur).
 */
Route::prefix('commerce')
    ->as('commerce.')
    ->middleware(['auth', 'verified', 'permission:module.commerce', 'feature.enabled:commerce.module'])
    ->group(function () {
        Route::get('/dashboard', [GcDashboardController::class, 'index'])->name('dashboard');
        Route::get('/categories', [GcCategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [GcCategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [GcCategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{id}/edit', [GcCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{id}', [GcCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{id}', [GcCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/products', [GcProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [GcProductController::class, 'create'])->name('products.create');
        Route::post('/products', [GcProductController::class, 'store'])->name('products.store');
        Route::get('/products/{id}/edit', [GcProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{id}', [GcProductController::class, 'update'])->name('products.update');
        Route::post('/products/{id}/toggle-status', [GcProductController::class, 'toggleStatus'])->name('products.toggle-status');
        Route::delete('/products/{id}', [GcProductController::class, 'destroy'])->name('products.destroy');

        Route::get('/sales', [GcSaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/create', [GcSaleController::class, 'create'])->name('sales.create');
        Route::post('/sales', [GcSaleController::class, 'store'])->name('sales.store');
        Route::post('/sales/quick-customer', [GcSaleController::class, 'quickCreateCustomer'])->name('sales.quick-customer');
        Route::post('/sales/{id}/finalize', [GcSaleController::class, 'finalize'])->name('sales.finalize');
        Route::get('/sales/{id}/receipt', [GcSaleController::class, 'receipt'])->name('sales.receipt');
        Route::get('/sales/{id}', [GcSaleController::class, 'show'])->name('sales.show');

        Route::get('/stock', [GcStockController::class, 'index'])->name('stock.index');
        Route::post('/stock/{id}/adjust', [GcStockController::class, 'adjust'])
            ->name('stock.adjust');

        // Transferts inter-magasins (Commerce) — plan avec multi-dépôt
        Route::middleware('feature.enabled:multi.depot')->group(function () {
            Route::get('/transfers', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcStockTransferController::class, 'index'])
                ->name('transfers.index');
            Route::get('/transfers/create', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcStockTransferController::class, 'create'])
                ->name('transfers.create');
            Route::post('/transfers', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcStockTransferController::class, 'store'])
                ->name('transfers.store');
            Route::get('/transfers/{id}', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcStockTransferController::class, 'show'])
                ->name('transfers.show');
            Route::post('/transfers/{id}/items', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcStockTransferController::class, 'addItem'])
                ->name('transfers.items.add');
            Route::put('/transfers/{id}/items/{itemId}', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcStockTransferController::class, 'updateItem'])
                ->name('transfers.items.update');
            Route::delete('/transfers/{id}/items/{itemId}', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcStockTransferController::class, 'removeItem'])
                ->name('transfers.items.remove');
            Route::post('/transfers/{id}/validate', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcStockTransferController::class, 'validate'])
                ->name('transfers.validate');
            Route::post('/transfers/{id}/cancel', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcStockTransferController::class, 'cancel'])
                ->name('transfers.cancel');
        });

        Route::get('/inventories', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcInventoryController::class, 'index'])
            ->name('inventories.index');
        Route::post('/inventories', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcInventoryController::class, 'store'])
            ->name('inventories.store');
        Route::get('/inventories/{id}', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcInventoryController::class, 'show'])
            ->name('inventories.show');
        Route::post('/inventories/{id}/start', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcInventoryController::class, 'start'])
            ->name('inventories.start');
        Route::post('/inventories/{id}/counts', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcInventoryController::class, 'updateCounts'])
            ->name('inventories.counts');
        Route::post('/inventories/{id}/validate', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcInventoryController::class, 'validate'])
            ->name('inventories.validate');
        Route::post('/inventories/{id}/cancel', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcInventoryController::class, 'cancel'])
            ->name('inventories.cancel');

        Route::get('/reports', [GcReportController::class, 'index'])->name('reports.index');

        Route::get('/suppliers', [GcSupplierController::class, 'index'])->name('suppliers.index');
        Route::get('/suppliers/create', [GcSupplierController::class, 'create'])->name('suppliers.create');
        Route::post('/suppliers', [GcSupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/{id}/edit', [GcSupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('/suppliers/{id}', [GcSupplierController::class, 'update'])->name('suppliers.update');
        Route::post('/suppliers/{id}/toggle-active', [GcSupplierController::class, 'toggleActive'])->name('suppliers.toggle-active');

        Route::get('/purchases', [GcPurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/create', [GcPurchaseController::class, 'create'])->name('purchases.create');
        Route::post('/purchases', [GcPurchaseController::class, 'store'])->name('purchases.store');
        Route::get('/purchases/{id}', [GcPurchaseController::class, 'show'])->name('purchases.show');
        Route::post('/purchases/{id}/receive', [GcPurchaseController::class, 'receive'])->name('purchases.receive');

        Route::get('/customers', [GcCustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/create', [GcCustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [GcCustomerController::class, 'store'])->name('customers.store');
        Route::put('/customers/{id}', [GcCustomerController::class, 'update'])->name('customers.update');

        // Assistant Commerce (nommé 'code')
        Route::post('/assistant/ask', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\CommerceAssistantController::class, 'ask'])
            ->middleware(['permission:commerce.assistant.use', 'feature.enabled:ai.assistant'])
            ->name('assistant.ask');

        // API vocale (STT + TTS) – transcription Whisper, synthèse vocale, paramètres
        Route::prefix('api/voice')->name('api.voice.')->group(function () {
            Route::post('/transcribe', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\CommerceVoiceController::class, 'transcribe'])
                ->middleware(['permission:commerce.assistant.voice', 'feature.enabled:ai.assistant'])
                ->name('transcribe');
            Route::post('/speak', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\CommerceVoiceController::class, 'speak'])
                ->middleware(['permission:commerce.assistant.voice', 'feature.enabled:ai.assistant'])
                ->name('speak');
            Route::get('/settings', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\CommerceVoiceController::class, 'settings'])
                ->middleware(['permission:commerce.assistant.voice', 'feature.enabled:ai.assistant'])
                ->name('settings');
            Route::put('/settings', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\CommerceVoiceController::class, 'updateSettings'])
                ->middleware(['permission:commerce.assistant.voice', 'feature.enabled:ai.assistant'])
                ->name('settings.update');
        });

        // Dépôts (création / édition = multi-dépôt)
        Route::get('/depots', [\App\Http\Controllers\DepotController::class, 'index'])->name('depots.index');
        Route::post('/depots', [\App\Http\Controllers\DepotController::class, 'store'])
            ->middleware('feature.enabled:multi.depot')
            ->name('depots.store');
        Route::put('/depots/{id}', [\App\Http\Controllers\DepotController::class, 'update'])
            ->middleware('feature.enabled:multi.depot')
            ->name('depots.update');
        Route::post('/depots/{id}/activate', [\App\Http\Controllers\DepotController::class, 'activate'])
            ->middleware('feature.enabled:multi.depot')
            ->name('depots.activate');
        Route::post('/depots/{id}/deactivate', [\App\Http\Controllers\DepotController::class, 'deactivate'])
            ->middleware('feature.enabled:multi.depot')
            ->name('depots.deactivate');

        // Vendeurs (gestion des vendeurs pour Commerce)
        Route::get('/sellers', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcSellerController::class, 'index'])->name('sellers.index');
        Route::post('/sellers', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcSellerController::class, 'store'])->name('sellers.store');
        Route::put('/sellers/{id}', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcSellerController::class, 'update'])->name('sellers.update');
        Route::delete('/sellers/{id}', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcSellerController::class, 'destroy'])->name('sellers.destroy');
        Route::post('/sellers/{id}/impersonate', [\Src\Infrastructure\GlobalCommerce\Http\Controllers\GcSellerController::class, 'impersonate'])->name('sellers.impersonate');

        // API - Mouvements de produits (GlobalCommerce)
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/product-movements', [GcProductMovementController::class, 'index'])
                ->name('product-movements.index');
            Route::get('/product-movements/export/pdf', [GcProductMovementController::class, 'exportGlobalPdf'])
                ->middleware('feature.enabled:reports.export.pdf')
                ->name('product-movements.pdf.global');
            Route::get('/product-movements/export/excel', [GcProductMovementController::class, 'exportExcel'])
                ->middleware('feature.enabled:reports.export.excel')
                ->name('product-movements.excel');
            Route::get('/product-movements/{id}/pdf', [GcProductMovementController::class, 'exportSinglePdf'])
                ->middleware('feature.enabled:reports.export.pdf')
                ->name('product-movements.pdf.single');
        });

        // Imports (Produits, Catégories, Fournisseurs, Clients)
        Route::get('/products/import/template', [GcProductController::class, 'importTemplate'])->name('products.import.template');
        Route::post('/products/import/preview', [GcProductController::class, 'importPreview'])->name('products.import.preview');
        Route::post('/products/import', [GcProductController::class, 'import'])->name('products.import');

        Route::get('/categories/import/template', [GcCategoryController::class, 'importTemplate'])->name('categories.import.template');
        Route::post('/categories/import', [GcCategoryController::class, 'import'])->name('categories.import');

        Route::get('/suppliers/import/template', [GcSupplierController::class, 'importTemplate'])->name('suppliers.import.template');
        Route::post('/suppliers/import', [GcSupplierController::class, 'import'])->name('suppliers.import');

        Route::get('/customers/import/template', [GcCustomerController::class, 'importTemplate'])->name('customers.import.template');
        Route::post('/customers/import', [GcCustomerController::class, 'import'])->name('customers.import');

        // Exports (PDF / Excel selon le plan)
        Route::prefix('exports')->name('exports.')->group(function () {
            Route::middleware('feature.enabled:reports.export.pdf')->group(function () {
                Route::get('/products/pdf', [GcExportController::class, 'productsPdf'])->name('products.pdf');
                Route::get('/categories/pdf', [GcExportController::class, 'categoriesPdf'])->name('categories.pdf');
                Route::get('/suppliers/pdf', [GcExportController::class, 'suppliersPdf'])->name('suppliers.pdf');
                Route::get('/customers/pdf', [GcExportController::class, 'customersPdf'])->name('customers.pdf');
                Route::get('/sales/pdf', [GcExportController::class, 'salesPdf'])->name('sales.pdf');
                Route::get('/purchases/pdf', [GcExportController::class, 'purchasesPdf'])->name('purchases.pdf');
                Route::get('/stock/pdf', [GcExportController::class, 'stockPdf'])->name('stock.pdf');
                Route::get('/reports/pdf', [GcExportController::class, 'reportPdf'])->name('reports.pdf');
            });
            Route::middleware('feature.enabled:reports.export.excel')->group(function () {
                Route::get('/products/excel', [GcExportController::class, 'productsExcel'])->name('products.excel');
                Route::get('/categories/excel', [GcExportController::class, 'categoriesExcel'])->name('categories.excel');
                Route::get('/suppliers/excel', [GcExportController::class, 'suppliersExcel'])->name('suppliers.excel');
                Route::get('/customers/excel', [GcExportController::class, 'customersExcel'])->name('customers.excel');
                Route::get('/sales/excel', [GcExportController::class, 'salesExcel'])->name('sales.excel');
                Route::get('/purchases/excel', [GcExportController::class, 'purchasesExcel'])->name('purchases.excel');
                Route::get('/stock/excel', [GcExportController::class, 'stockExcel'])->name('stock.excel');
                Route::get('/reports/excel', [GcExportController::class, 'reportExcel'])->name('reports.excel');
            });
        });
    });
