<?php

use Illuminate\Support\Facades\Route;
use Src\Infrastructure\Hardware\Http\Controllers\HardwareDashboardController;
use Src\Infrastructure\Quincaillerie\Http\Controllers\ProductController as QuincaillerieProductController;
use Src\Infrastructure\Quincaillerie\Http\Controllers\CategoryController as QuincaillerieCategoryController;
use Src\Infrastructure\Pharmacy\Http\Controllers\ProductMovementController;
use Src\Infrastructure\Quincaillerie\Http\Controllers\SupplierController as QuincaillerieSupplierController;
use Src\Infrastructure\Pharmacy\Http\Controllers\SupplierPricingController;
use Src\Infrastructure\Pharmacy\Http\Controllers\PurchaseController;
use Src\Infrastructure\Pharmacy\Http\Controllers\SaleController;
use Src\Infrastructure\Pharmacy\Http\Controllers\CashRegisterController;
use Src\Infrastructure\Quincaillerie\Http\Controllers\CustomerController as QuincaillerieCustomerController;
use Src\Infrastructure\Pharmacy\Http\Controllers\StockController;
use Src\Infrastructure\Pharmacy\Http\Controllers\PharmacyReportController;
use Src\Infrastructure\Pharmacy\Http\Controllers\ExportController;

/**
 * DDD Hardware (Quincaillerie) Module Routes
 * Produits, catégories, fournisseurs et clients : contrôleurs Quincaillerie (module isolé).
 * Autres ressources (ventes, achats, etc.) : temporairement Pharmacy (à migrer vers Quincaillerie).
 */
Route::prefix('hardware')
    ->as('hardware.')
    ->middleware(['auth', 'verified', 'permission:module.hardware'])
    ->group(function () {
        Route::get('/dashboard', [HardwareDashboardController::class, 'index'])
            ->name('dashboard');

        // Produits — Module Quincaillerie (contrôleur et données dédiés)
        Route::get('/products', [QuincaillerieProductController::class, 'index'])
            ->middleware('permission:hardware.product.view|hardware.product.manage')
            ->name('products');
        Route::get('/products/generate-code', [QuincaillerieProductController::class, 'generateCode'])
            ->middleware('permission:hardware.product.manage|hardware.product.create')
            ->name('products.generate-code');
        Route::get('/products/create', [QuincaillerieProductController::class, 'create'])
            ->middleware('permission:hardware.product.manage')
            ->name('products.create');
        Route::post('/products', [QuincaillerieProductController::class, 'store'])
            ->middleware('permission:hardware.product.manage')
            ->name('products.store');
        Route::get('/products/{id}', [QuincaillerieProductController::class, 'show'])
            ->middleware('permission:hardware.product.view|hardware.product.manage')
            ->name('products.show');
        Route::get('/products/{id}/edit', [QuincaillerieProductController::class, 'edit'])
            ->middleware('permission:hardware.product.manage')
            ->name('products.edit');
        Route::put('/products/{id}', [QuincaillerieProductController::class, 'update'])
            ->middleware('permission:hardware.product.manage')
            ->name('products.update');
        Route::delete('/products/{id}', [QuincaillerieProductController::class, 'destroy'])
            ->middleware('permission:hardware.product.manage')
            ->name('products.destroy');
        Route::post('/products/{id}/duplicate-to-depot', [QuincaillerieProductController::class, 'duplicateToDepot'])
            ->middleware('permission:hardware.product.manage')
            ->name('products.duplicate-to-depot');

        // API mouvements de stock (historique produits)
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/product-movements', [ProductMovementController::class, 'index'])
                ->middleware('permission:hardware.stock.movement.view|stock.movement.view')
                ->name('product-movements.index');
            Route::get('/product-movements/export/pdf', [ProductMovementController::class, 'exportGlobalPdf'])
                ->middleware('permission:stock.movement.view')
                ->name('product-movements.pdf.global');
            Route::get('/product-movements/{id}/pdf', [ProductMovementController::class, 'exportSinglePdf'])
                ->middleware('permission:stock.movement.view')
                ->name('product-movements.pdf.single');
        });

        // Fournisseurs — Module Quincaillerie (contrôleur et données dédiés)
        Route::get('/suppliers', [QuincaillerieSupplierController::class, 'index'])
            ->middleware('permission:hardware.supplier.view')
            ->name('suppliers.index');
        Route::post('/suppliers', [QuincaillerieSupplierController::class, 'store'])
            ->middleware('permission:hardware.supplier.create')
            ->name('suppliers.store');
        Route::get('/suppliers/active', [QuincaillerieSupplierController::class, 'listActive'])
            ->middleware('permission:hardware.supplier.view|hardware.purchases.manage')
            ->name('suppliers.active');
        Route::get('/suppliers/{id}', [QuincaillerieSupplierController::class, 'show'])
            ->middleware('permission:hardware.supplier.view')
            ->name('suppliers.show');
        Route::put('/suppliers/{id}', [QuincaillerieSupplierController::class, 'update'])
            ->middleware('permission:hardware.supplier.edit')
            ->name('suppliers.update');
        Route::post('/suppliers/{id}/activate', [QuincaillerieSupplierController::class, 'activate'])
            ->middleware('permission:hardware.supplier.activate')
            ->name('suppliers.activate');
        Route::post('/suppliers/{id}/deactivate', [QuincaillerieSupplierController::class, 'deactivate'])
            ->middleware('permission:hardware.supplier.deactivate')
            ->name('suppliers.deactivate');

        // Prix fournisseur-produit
        Route::get('/suppliers/{supplierId}/prices', [SupplierPricingController::class, 'index'])
            ->middleware('permission:hardware.supplier.pricing.view')
            ->name('suppliers.prices.index');
        Route::post('/supplier-prices', [SupplierPricingController::class, 'store'])
            ->middleware('permission:hardware.supplier.pricing.manage')
            ->name('suppliers.prices.store');
        Route::get('/suppliers/{supplierId}/products/{productId}/price', [SupplierPricingController::class, 'getPrice'])
            ->middleware('permission:hardware.supplier.pricing.view')
            ->name('suppliers.prices.get');
        Route::get('/products/{productId}/supplier-prices', [SupplierPricingController::class, 'getProductPrices'])
            ->middleware('permission:hardware.supplier.pricing.view')
            ->name('products.supplier-prices');
        Route::delete('/supplier-prices/{id}', [SupplierPricingController::class, 'destroy'])
            ->middleware('permission:hardware.supplier.pricing.manage')
            ->name('suppliers.prices.destroy');

        // Bons de commande (achats)
        Route::get('/purchases', [PurchaseController::class, 'index'])
            ->middleware('permission:hardware.purchases.view|hardware.purchases.manage')
            ->name('purchases.index');
        Route::get('/purchases/create', [PurchaseController::class, 'create'])
            ->middleware('permission:hardware.purchases.manage')
            ->name('purchases.create');
        Route::post('/purchases', [PurchaseController::class, 'store'])
            ->middleware('permission:hardware.purchases.manage')
            ->name('purchases.store');
        Route::get('/purchases/{id}', [PurchaseController::class, 'show'])
            ->middleware('permission:hardware.purchases.view|hardware.purchases.manage')
            ->name('purchases.show');
        Route::post('/purchases/{id}/confirm', [PurchaseController::class, 'confirm'])
            ->middleware('permission:hardware.purchases.manage')
            ->name('purchases.confirm');
        Route::post('/purchases/{id}/receive', [PurchaseController::class, 'receive'])
            ->middleware('permission:hardware.purchases.receive|hardware.purchases.manage')
            ->name('purchases.receive');
        Route::post('/purchases/{id}/cancel', [PurchaseController::class, 'cancel'])
            ->middleware('permission:hardware.purchases.manage')
            ->name('purchases.cancel');

        // Ventes (Sales)
        Route::get('/sales', [SaleController::class, 'index'])
            ->middleware('permission:hardware.sales.view|hardware.sales.manage')
            ->name('sales.index');
        Route::get('/sales/create', [SaleController::class, 'create'])
            ->middleware('permission:hardware.sales.manage')
            ->name('sales.create');
        Route::post('/sales', [SaleController::class, 'store'])
            ->middleware('permission:hardware.sales.manage')
            ->name('sales.store');
        Route::post('/sales/quick-customer', [SaleController::class, 'quickCreateCustomer'])
            ->middleware('permission:hardware.sales.manage')
            ->name('sales.quick-customer');
        Route::get('/sales/{id}', [SaleController::class, 'show'])
            ->middleware('permission:hardware.sales.view|hardware.sales.manage')
            ->name('sales.show');
        Route::put('/sales/{id}', [SaleController::class, 'update'])
            ->middleware('permission:hardware.sales.manage')
            ->name('sales.update');
        Route::post('/sales/{id}/finalize', [SaleController::class, 'finalize'])
            ->middleware('permission:hardware.sales.manage')
            ->name('sales.finalize');
        Route::post('/sales/{id}/cancel', [SaleController::class, 'cancel'])
            ->middleware('permission:hardware.sales.cancel|hardware.sales.manage')
            ->name('sales.cancel');
        Route::get('/sales/{id}/receipt', [SaleController::class, 'receipt'])
            ->middleware('permission:hardware.sales.view|hardware.sales.manage')
            ->name('sales.receipt');
        Route::post('/sales/{id}/email-receipt', [SaleController::class, 'emailReceipt'])
            ->middleware('permission:hardware.sales.view|hardware.sales.manage')
            ->name('sales.email-receipt');

        // Caisse (cash registers)
        Route::get('/cash-registers', [CashRegisterController::class, 'index'])
            ->middleware('permission:hardware.sales.view|hardware.sales.manage')
            ->name('cash-registers.index');
        Route::post('/cash-registers', [CashRegisterController::class, 'store'])
            ->middleware('permission:hardware.sales.manage')
            ->name('cash-registers.store');
        Route::post('/cash-registers/{id}/open', [CashRegisterController::class, 'openSession'])
            ->middleware('permission:hardware.sales.manage')
            ->name('cash-registers.open');
        Route::post('/cash-registers/sessions/{sessionId}/close', [CashRegisterController::class, 'closeSession'])
            ->middleware('permission:hardware.sales.manage')
            ->name('cash-registers.sessions.close');

        // Clients — Module Quincaillerie (contrôleur et données dédiés)
        Route::get('/customers', [QuincaillerieCustomerController::class, 'index'])
            ->middleware('permission:hardware.customer.view')
            ->name('customers.index');
        Route::post('/customers', [QuincaillerieCustomerController::class, 'store'])
            ->middleware('permission:hardware.customer.create')
            ->name('customers.store');
        Route::get('/customers/active', [QuincaillerieCustomerController::class, 'listActive'])
            ->middleware('permission:hardware.customer.view|hardware.sales.manage')
            ->name('customers.active');
        Route::get('/customers/{id}', [QuincaillerieCustomerController::class, 'show'])
            ->middleware('permission:hardware.customer.view')
            ->name('customers.show');
        Route::put('/customers/{id}', [QuincaillerieCustomerController::class, 'update'])
            ->middleware('permission:hardware.customer.edit')
            ->name('customers.update');
        Route::post('/customers/{id}/activate', [QuincaillerieCustomerController::class, 'activate'])
            ->middleware('permission:hardware.customer.activate')
            ->name('customers.activate');
        Route::post('/customers/{id}/deactivate', [QuincaillerieCustomerController::class, 'deactivate'])
            ->middleware('permission:hardware.customer.deactivate')
            ->name('customers.deactivate');

        // Stock (mouvements et liste : Pharmacy pour l’instant ; pas de updateStock Quincaillerie tant que stock dédié)
        Route::get('/stock', [StockController::class, 'index'])
            ->middleware('permission:hardware.stock.view|hardware.stock.manage')
            ->name('stock.index');
        Route::get('/stock/movements', [StockController::class, 'movementsIndex'])
            ->middleware('permission:hardware.stock.movement.view|hardware.stock.manage')
            ->name('stock.movements.index');
        Route::get('/stock/{id}/movements', [StockController::class, 'movements'])
            ->middleware('permission:hardware.stock.movement.view|hardware.stock.manage')
            ->name('stock.movements');

        // Rapports
        Route::get('/reports', [PharmacyReportController::class, 'index'])
            ->middleware('permission:hardware.sales.view|hardware.report.view')
            ->name('reports.index');

        // Catégories — Module Quincaillerie (contrôleur et données dédiés)
        Route::get('/categories', [QuincaillerieCategoryController::class, 'index'])
            ->middleware('permission:hardware.category.view|hardware.category.create|hardware.category.update|hardware.category.delete')
            ->name('categories.index');
        Route::post('/categories', [QuincaillerieCategoryController::class, 'store'])
            ->middleware('permission:hardware.category.create')
            ->name('categories.store');
        Route::put('/categories/{id}', [QuincaillerieCategoryController::class, 'update'])
            ->middleware('permission:hardware.category.update')
            ->name('categories.update');
        Route::delete('/categories/{id}', [QuincaillerieCategoryController::class, 'destroy'])
            ->middleware('permission:hardware.category.delete')
            ->name('categories.destroy');

        // Dépôts - Gestion complète
        Route::get('/depots', [\App\Http\Controllers\DepotController::class, 'index'])
            ->middleware('permission:hardware.warehouse.view_all|hardware.warehouse.view')
            ->name('depots.index');
        Route::post('/depots', [\App\Http\Controllers\DepotController::class, 'store'])
            ->middleware('permission:hardware.warehouse.create')
            ->name('depots.store');
        Route::put('/depots/{id}', [\App\Http\Controllers\DepotController::class, 'update'])
            ->middleware('permission:hardware.warehouse.update')
            ->name('depots.update');
        Route::post('/depots/{id}/activate', [\App\Http\Controllers\DepotController::class, 'activate'])
            ->middleware('permission:hardware.warehouse.activate')
            ->name('depots.activate');
        Route::post('/depots/{id}/deactivate', [\App\Http\Controllers\DepotController::class, 'deactivate'])
            ->middleware('permission:hardware.warehouse.deactivate')
            ->name('depots.deactivate');

        // Exports (fournisseurs + bons de commande + ventes)
        Route::prefix('exports')->name('exports.')->group(function () {
            Route::get('/sales/pdf', [ExportController::class, 'salesPdf'])
                ->middleware('permission:hardware.sales.view')
                ->name('sales.pdf');
            Route::get('/sales/excel', [ExportController::class, 'salesExcel'])
                ->middleware('permission:hardware.sales.view')
                ->name('sales.excel');
            Route::get('/stock/pdf', [ExportController::class, 'stockPdf'])
                ->middleware('permission:hardware.stock.view')
                ->name('stock.pdf');
            Route::get('/stock/excel', [ExportController::class, 'stockExcel'])
                ->middleware('permission:hardware.stock.view')
                ->name('stock.excel');
            Route::get('/suppliers/pdf', [ExportController::class, 'suppliersPdf'])
                ->middleware('permission:hardware.supplier.view')
                ->name('suppliers.pdf');
            Route::get('/suppliers/excel', [ExportController::class, 'suppliersExcel'])
                ->middleware('permission:hardware.supplier.view')
                ->name('suppliers.excel');
            Route::get('/purchases/pdf', [ExportController::class, 'purchasesPdf'])
                ->middleware('permission:hardware.purchases.view')
                ->name('purchases.pdf');
            Route::get('/purchases/excel', [ExportController::class, 'purchasesExcel'])
                ->middleware('permission:hardware.purchases.view')
                ->name('purchases.excel');
            Route::get('/customers/pdf', [ExportController::class, 'customersPdf'])
                ->middleware('permission:hardware.customer.view')
                ->name('customers.pdf');
            Route::get('/customers/excel', [ExportController::class, 'customersExcel'])
                ->middleware('permission:hardware.customer.view')
                ->name('customers.excel');
            Route::get('/movements/pdf', [ExportController::class, 'movementsPdf'])
                ->middleware('permission:hardware.stock.movement.view')
                ->name('movements.pdf');
            Route::get('/movements/excel', [ExportController::class, 'movementsExcel'])
                ->middleware('permission:hardware.stock.movement.view')
                ->name('movements.excel');
            Route::get('/reports/pdf', [ExportController::class, 'reportPdf'])
                ->middleware('permission:hardware.sales.view|hardware.report.view')
                ->name('reports.pdf');
            Route::get('/reports/excel', [ExportController::class, 'reportExcel'])
                ->middleware('permission:hardware.sales.view|hardware.report.view')
                ->name('reports.excel');
        });
    });

