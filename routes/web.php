<?php

use App\Http\Controllers\ProfileController;
use Src\Infrastructure\Admin\Http\Controllers\AdminController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/**
 * DDD Pharmacy Module Routes
 */
require __DIR__.'/pharmacy.php';

/**
 * Public Routes - Landing Page
 */
Route::get('/', function () {
    return Inertia::render('Landing');
})->name('landing');

/**
 * Public Routes - Login (Welcome page)
 */
Route::get('/login', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('login');

/**
 * Protected Routes - Dashboard and Profile
 */
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Contexte dépôt (sélection avant accès produits/stock)
    Route::post('/depot/switch', [\App\Http\Controllers\DepotController::class, 'switch'])
        ->name('depot.switch');
    
    // Global Search API
    Route::get('/api/search', [\Src\Infrastructure\Search\Http\Controllers\GlobalSearchController::class, 'search'])
        ->name('api.search');
});

/**
 * Admin Routes - ROOT user only
 */
Route::middleware(['auth', 'verified', 'root', 'permission'])->group(function () {
    // Sélection de tenant
    Route::get('/admin/select-tenant', [AdminController::class, 'selectTenant'])
        ->name('admin.tenants.select.view');

    // Dashboard d'un tenant spécifique
    Route::get('/admin/tenant/{id}/dashboard', [AdminController::class, 'tenantDashboard'])
        ->name('admin.tenants.dashboard.view');

    // Gestion globale
    Route::get('/admin/tenants', [AdminController::class, 'manageTenants'])
        ->name('admin.tenants.view');

    Route::get('/admin/users', [AdminController::class, 'manageUsers'])
        ->name('admin.users.view');

    // Actions sur tenants
    Route::post('/admin/tenant/{id}/toggle', [AdminController::class, 'toggleTenant'])
        ->name('admin.tenants.update');

    // Actions sur utilisateurs
    Route::post('/admin/user/{id}/toggle', [AdminController::class, 'toggleUser'])
        ->name('admin.users.update');
    
    // Gestion complète des utilisateurs
    Route::prefix('admin/users')->name('admin.users.')->group(function () {
        Route::post('/{id}/assign-role', [\Src\Infrastructure\User\Http\Controllers\UserManagementController::class, 'assignRole'])
            ->middleware('permission:users.assign_role')
            ->name('assign-role');
        
        Route::post('/{id}/status', [\Src\Infrastructure\User\Http\Controllers\UserManagementController::class, 'updateStatus'])
            ->name('update-status');
        
        Route::post('/{id}/reset-password', [\Src\Infrastructure\User\Http\Controllers\UserManagementController::class, 'resetPassword'])
            ->middleware('permission:users.reset_password')
            ->name('reset-password');
        
        Route::delete('/{id}', [\Src\Infrastructure\User\Http\Controllers\UserManagementController::class, 'delete'])
            ->middleware('permission:users.delete')
            ->name('delete');
        
        Route::post('/{id}/impersonate', [\Src\Infrastructure\User\Http\Controllers\UserManagementController::class, 'impersonate'])
            ->middleware('permission:users.impersonate')
            ->name('impersonate');
    });
    
});

/**
 * Route pour arrêter l'impersonation - Accessible à tous les utilisateurs authentifiés
 * (même en impersonation, car on doit pouvoir revenir au compte original)
 */
Route::middleware(['auth'])->group(function () {
    Route::post('/admin/stop-impersonation', [\Src\Infrastructure\User\Http\Controllers\UserManagementController::class, 'stopImpersonation'])
        ->name('admin.stop-impersonation');
});

/**
 * Access Manager Routes - RBAC (Rôles & Permissions)
 * Toutes les routes sont protégées par permissions spécifiques
 */
Route::middleware(['auth', 'verified', 'root'])->group(function () {
    // Gestion des rôles
    Route::get('/admin/access-manager/roles', [\App\Http\Controllers\Admin\AccessManagerController::class, 'roles'])
        ->middleware('permission:access.roles.view')
        ->name('admin.access.roles');
    
    Route::get('/admin/access-manager/roles/{id}', [\App\Http\Controllers\Admin\AccessManagerController::class, 'getRole'])
        ->middleware('permission:access.roles.update')
        ->name('admin.access.roles.get');
    
    Route::post('/admin/access-manager/roles', [\App\Http\Controllers\Admin\AccessManagerController::class, 'createRole'])
        ->middleware('permission:access.roles.create')
        ->name('admin.access.roles.store');
    
    Route::put('/admin/access-manager/roles/{id}', [\App\Http\Controllers\Admin\AccessManagerController::class, 'updateRole'])
        ->middleware('permission:access.roles.update')
        ->name('admin.access.roles.update');
    
    Route::delete('/admin/access-manager/roles/{id}', [\App\Http\Controllers\Admin\AccessManagerController::class, 'deleteRole'])
        ->middleware('permission:access.roles.delete')
        ->name('admin.access.roles.delete');
    
    // Gestion des permissions
    Route::get('/admin/access-manager/permissions', [\App\Http\Controllers\Admin\AccessManagerController::class, 'permissions'])
        ->middleware('permission:access.permissions.view')
        ->name('admin.access.permissions');
    
    Route::delete('/admin/access-manager/permissions/{id}', [\App\Http\Controllers\Admin\AccessManagerController::class, 'deletePermission'])
        ->middleware('permission:access.permissions.delete')
        ->name('admin.access.permissions.delete');
    
    Route::post('/admin/access-manager/permissions/sync', [\App\Http\Controllers\Admin\AccessManagerController::class, 'syncPermissions'])
        ->middleware('permission:access.permissions.sync')
        ->name('admin.access.permissions.sync');
});

/**
 * Pharmacy Module Routes
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('pharmacy')->name('pharmacy.')->group(function () {
        // Dashboard (stats, pas la liste produits)
        Route::get('/dashboard', [\Src\Infrastructure\Pharmacy\Http\Controllers\PharmacyDashboardController::class, 'index'])
            ->middleware('permission:module.pharmacy')
            ->name('dashboard');

        // Rapports
        Route::get('/reports', [\Src\Infrastructure\Pharmacy\Http\Controllers\PharmacyReportController::class, 'index'])
            ->middleware('permission:pharmacy.sales.view|pharmacy.report.view')
            ->name('reports.index');

        // Products
        // Support multiple permission formats for compatibility
        Route::get('/products', [\Src\Infrastructure\Pharmacy\Http\Controllers\ProductController::class, 'index'])
            ->middleware('permission:pharmacy.pharmacy.product.manage|pharmacy.product.manage')
            ->name('products');

        // Génération automatique de code produit
        Route::get('/products/generate-code', [\Src\Infrastructure\Pharmacy\Http\Controllers\ProductController::class, 'generateCode'])
            ->middleware('permission:pharmacy.pharmacy.product.manage|pharmacy.product.manage')
            ->name('products.generate-code');

        // Exports Produits
        Route::get('/products/export/pdf', [\Src\Infrastructure\Pharmacy\Http\Controllers\ProductController::class, 'exportPdf'])
            ->middleware('permission:pharmacy.pharmacy.product.manage|pharmacy.product.manage')
            ->name('products.export.pdf');

        Route::get('/products/export/excel', [\Src\Infrastructure\Pharmacy\Http\Controllers\ProductController::class, 'exportExcel'])
            ->middleware('permission:pharmacy.pharmacy.product.manage|pharmacy.product.manage')
            ->name('products.export.excel');

        Route::post('/products/import', [\Src\Infrastructure\Pharmacy\Http\Controllers\ProductController::class, 'import'])
            ->middleware('permission:pharmacy.product.import')
            ->name('products.import');
        
        Route::get('/products/create', [\Src\Infrastructure\Pharmacy\Http\Controllers\ProductController::class, 'create'])
            ->middleware('permission:pharmacy.pharmacy.product.manage|pharmacy.product.manage')
            ->name('products.create');
        
        Route::post('/products', [\Src\Infrastructure\Pharmacy\Http\Controllers\ProductController::class, 'store'])
            ->middleware('permission:pharmacy.pharmacy.product.manage|pharmacy.product.manage')
            ->name('products.store');
        
        Route::get('/products/{id}', [\Src\Infrastructure\Pharmacy\Http\Controllers\ProductController::class, 'show'])
            ->middleware('permission:pharmacy.pharmacy.product.manage|pharmacy.product.manage')
            ->name('products.show');
        
        Route::get('/products/{id}/edit', [\Src\Infrastructure\Pharmacy\Http\Controllers\ProductController::class, 'edit'])
            ->middleware('permission:pharmacy.pharmacy.product.manage|pharmacy.product.manage')
            ->name('products.edit');
        
        Route::put('/products/{id}', [\Src\Infrastructure\Pharmacy\Http\Controllers\ProductController::class, 'update'])
            ->middleware('permission:pharmacy.pharmacy.product.manage|pharmacy.product.manage')
            ->name('products.update');
        
        Route::delete('/products/{id}', [\Src\Infrastructure\Pharmacy\Http\Controllers\ProductController::class, 'destroy'])
            ->middleware('permission:pharmacy.pharmacy.product.manage|pharmacy.product.manage')
            ->name('products.destroy');

        // Stock
        Route::get('/stock', [\Src\Infrastructure\Pharmacy\Http\Controllers\StockController::class, 'index'])
            ->middleware('permission:pharmacy.pharmacy.stock.manage|stock.view')
            ->name('stock.index');

        Route::get('/stock/movements', [\Src\Infrastructure\Pharmacy\Http\Controllers\StockController::class, 'movementsIndex'])
            ->middleware('permission:stock.movement.view|pharmacy.pharmacy.stock.manage')
            ->name('stock.movements.index');
        Route::get('/stock/{id}/movements', [\Src\Infrastructure\Pharmacy\Http\Controllers\StockController::class, 'movements'])
            ->middleware('permission:stock.movement.view|pharmacy.pharmacy.stock.manage')
            ->name('stock.movements');

        // ========== INVENTAIRES ==========
        Route::get('/inventories', [\Src\Infrastructure\Pharmacy\Http\Controllers\InventoryController::class, 'index'])
            ->middleware('permission:inventory.view')
            ->name('inventories.index');
        Route::post('/inventories', [\Src\Infrastructure\Pharmacy\Http\Controllers\InventoryController::class, 'store'])
            ->middleware('permission:inventory.create')
            ->name('inventories.store');
        Route::get('/inventories/{id}', [\Src\Infrastructure\Pharmacy\Http\Controllers\InventoryController::class, 'show'])
            ->middleware('permission:inventory.view')
            ->name('inventories.show');
        Route::post('/inventories/{id}/start', [\Src\Infrastructure\Pharmacy\Http\Controllers\InventoryController::class, 'start'])
            ->middleware('permission:inventory.edit')
            ->name('inventories.start');
        Route::post('/inventories/{id}/counts', [\Src\Infrastructure\Pharmacy\Http\Controllers\InventoryController::class, 'updateCounts'])
            ->middleware('permission:inventory.edit')
            ->name('inventories.counts');
        Route::post('/inventories/{id}/counts/{productId}', [\Src\Infrastructure\Pharmacy\Http\Controllers\InventoryController::class, 'updateSingleCount'])
            ->middleware('permission:inventory.edit')
            ->name('inventories.counts.single');
        Route::post('/inventories/{id}/validate', [\Src\Infrastructure\Pharmacy\Http\Controllers\InventoryController::class, 'validate'])
            ->middleware('permission:inventory.validate')
            ->name('inventories.validate');
        Route::post('/inventories/{id}/cancel', [\Src\Infrastructure\Pharmacy\Http\Controllers\InventoryController::class, 'cancel'])
            ->middleware('permission:inventory.cancel')
            ->name('inventories.cancel');

        // Sales (Vente)
        Route::get('/sales', [\Src\Infrastructure\Pharmacy\Http\Controllers\SaleController::class, 'index'])
            ->middleware('permission:pharmacy.sales.view|pharmacy.sales.manage')
            ->name('sales.index');
        Route::get('/sales/create', [\Src\Infrastructure\Pharmacy\Http\Controllers\SaleController::class, 'create'])
            ->middleware('permission:pharmacy.sales.manage')
            ->name('sales.create');
        Route::post('/sales', [\Src\Infrastructure\Pharmacy\Http\Controllers\SaleController::class, 'store'])
            ->middleware('permission:pharmacy.sales.manage')
            ->name('sales.store');
        Route::get('/sales/{id}', [\Src\Infrastructure\Pharmacy\Http\Controllers\SaleController::class, 'show'])
            ->middleware('permission:pharmacy.sales.view|pharmacy.sales.manage')
            ->name('sales.show');
        Route::put('/sales/{id}', [\Src\Infrastructure\Pharmacy\Http\Controllers\SaleController::class, 'update'])
            ->middleware('permission:pharmacy.sales.manage')
            ->name('sales.update');
        Route::post('/sales/{id}/finalize', [\Src\Infrastructure\Pharmacy\Http\Controllers\SaleController::class, 'finalize'])
            ->middleware('permission:pharmacy.sales.manage')
            ->name('sales.finalize');
        Route::post('/sales/{id}/cancel', [\Src\Infrastructure\Pharmacy\Http\Controllers\SaleController::class, 'cancel'])
            ->middleware('permission:pharmacy.sales.cancel|pharmacy.sales.manage')
            ->name('sales.cancel');

        // Purchases (Achat)
        Route::get('/purchases', [\Src\Infrastructure\Pharmacy\Http\Controllers\PurchaseController::class, 'index'])
            ->middleware('permission:pharmacy.purchases.view|pharmacy.purchases.manage')
            ->name('purchases.index');
        Route::get('/purchases/create', [\Src\Infrastructure\Pharmacy\Http\Controllers\PurchaseController::class, 'create'])
            ->middleware('permission:pharmacy.purchases.manage')
            ->name('purchases.create');
        Route::post('/purchases', [\Src\Infrastructure\Pharmacy\Http\Controllers\PurchaseController::class, 'store'])
            ->middleware('permission:pharmacy.purchases.manage')
            ->name('purchases.store');
        Route::get('/purchases/{id}', [\Src\Infrastructure\Pharmacy\Http\Controllers\PurchaseController::class, 'show'])
            ->middleware('permission:pharmacy.purchases.view|pharmacy.purchases.manage')
            ->name('purchases.show');
        Route::post('/purchases/{id}/confirm', [\Src\Infrastructure\Pharmacy\Http\Controllers\PurchaseController::class, 'confirm'])
            ->middleware('permission:pharmacy.purchases.manage')
            ->name('purchases.confirm');
        Route::post('/purchases/{id}/receive', [\Src\Infrastructure\Pharmacy\Http\Controllers\PurchaseController::class, 'receive'])
            ->middleware('permission:pharmacy.purchases.receive|pharmacy.purchases.manage')
            ->name('purchases.receive');
        Route::post('/purchases/{id}/cancel', [\Src\Infrastructure\Pharmacy\Http\Controllers\PurchaseController::class, 'cancel'])
            ->middleware('permission:pharmacy.purchases.manage')
            ->name('purchases.cancel');

        // Suppliers (Fournisseurs) - utilise un drawer, pas de pages séparées create/edit
        Route::get('/suppliers', [\Src\Infrastructure\Pharmacy\Http\Controllers\SupplierController::class, 'index'])
            ->middleware('permission:pharmacy.supplier.view')
            ->name('suppliers.index');
        Route::post('/suppliers', [\Src\Infrastructure\Pharmacy\Http\Controllers\SupplierController::class, 'store'])
            ->middleware('permission:pharmacy.supplier.create')
            ->name('suppliers.store');
        Route::get('/suppliers/active', [\Src\Infrastructure\Pharmacy\Http\Controllers\SupplierController::class, 'listActive'])
            ->middleware('permission:pharmacy.supplier.view|pharmacy.purchases.manage')
            ->name('suppliers.active');
        Route::get('/suppliers/{id}', [\Src\Infrastructure\Pharmacy\Http\Controllers\SupplierController::class, 'show'])
            ->middleware('permission:pharmacy.supplier.view')
            ->name('suppliers.show');
        Route::put('/suppliers/{id}', [\Src\Infrastructure\Pharmacy\Http\Controllers\SupplierController::class, 'update'])
            ->middleware('permission:pharmacy.supplier.edit')
            ->name('suppliers.update');
        Route::post('/suppliers/{id}/activate', [\Src\Infrastructure\Pharmacy\Http\Controllers\SupplierController::class, 'activate'])
            ->middleware('permission:pharmacy.supplier.activate')
            ->name('suppliers.activate');
        Route::post('/suppliers/{id}/deactivate', [\Src\Infrastructure\Pharmacy\Http\Controllers\SupplierController::class, 'deactivate'])
            ->middleware('permission:pharmacy.supplier.deactivate')
            ->name('suppliers.deactivate');

        // Supplier Product Pricing (Prix fournisseur-produit)
        Route::get('/suppliers/{supplierId}/prices', [\Src\Infrastructure\Pharmacy\Http\Controllers\SupplierPricingController::class, 'index'])
            ->middleware('permission:pharmacy.supplier.pricing.view')
            ->name('suppliers.prices.index');
        Route::post('/supplier-prices', [\Src\Infrastructure\Pharmacy\Http\Controllers\SupplierPricingController::class, 'store'])
            ->middleware('permission:pharmacy.supplier.pricing.manage')
            ->name('suppliers.prices.store');
        Route::get('/suppliers/{supplierId}/products/{productId}/price', [\Src\Infrastructure\Pharmacy\Http\Controllers\SupplierPricingController::class, 'getPrice'])
            ->middleware('permission:pharmacy.supplier.pricing.view')
            ->name('suppliers.prices.get');
        Route::get('/products/{productId}/supplier-prices', [\Src\Infrastructure\Pharmacy\Http\Controllers\SupplierPricingController::class, 'getProductPrices'])
            ->middleware('permission:pharmacy.supplier.pricing.view')
            ->name('products.supplier-prices');
        Route::delete('/supplier-prices/{id}', [\Src\Infrastructure\Pharmacy\Http\Controllers\SupplierPricingController::class, 'destroy'])
            ->middleware('permission:pharmacy.supplier.pricing.manage')
            ->name('suppliers.prices.destroy');

        // Customers (Clients)
        Route::get('/customers', [\Src\Infrastructure\Pharmacy\Http\Controllers\CustomerController::class, 'index'])
            ->middleware('permission:pharmacy.customer.view')
            ->name('customers.index');
        Route::post('/customers', [\Src\Infrastructure\Pharmacy\Http\Controllers\CustomerController::class, 'store'])
            ->middleware('permission:pharmacy.customer.create')
            ->name('customers.store');
        Route::get('/customers/active', [\Src\Infrastructure\Pharmacy\Http\Controllers\CustomerController::class, 'listActive'])
            ->middleware('permission:pharmacy.customer.view|pharmacy.sales.manage')
            ->name('customers.active');
        Route::get('/customers/{id}', [\Src\Infrastructure\Pharmacy\Http\Controllers\CustomerController::class, 'show'])
            ->middleware('permission:pharmacy.customer.view')
            ->name('customers.show');
        Route::put('/customers/{id}', [\Src\Infrastructure\Pharmacy\Http\Controllers\CustomerController::class, 'update'])
            ->middleware('permission:pharmacy.customer.edit')
            ->name('customers.update');
        Route::post('/customers/{id}/activate', [\Src\Infrastructure\Pharmacy\Http\Controllers\CustomerController::class, 'activate'])
            ->middleware('permission:pharmacy.customer.activate')
            ->name('customers.activate');
        Route::post('/customers/{id}/deactivate', [\Src\Infrastructure\Pharmacy\Http\Controllers\CustomerController::class, 'deactivate'])
            ->middleware('permission:pharmacy.customer.deactivate')
            ->name('customers.deactivate');

        // Sellers (Vendeurs) - Gestion des vendeurs de la pharmacie
        Route::get('/sellers', [\Src\Infrastructure\Pharmacy\Http\Controllers\SellerController::class, 'index'])
            ->middleware('permission:pharmacy.seller.view')
            ->name('sellers.index');
        
        Route::post('/sellers', [\Src\Infrastructure\Pharmacy\Http\Controllers\SellerController::class, 'store'])
            ->middleware('permission:pharmacy.seller.create')
            ->name('sellers.store');
        
        Route::put('/sellers/{id}', [\Src\Infrastructure\Pharmacy\Http\Controllers\SellerController::class, 'update'])
            ->middleware('permission:pharmacy.seller.edit')
            ->name('sellers.update');
        
        Route::delete('/sellers/{id}', [\Src\Infrastructure\Pharmacy\Http\Controllers\SellerController::class, 'destroy'])
            ->middleware('permission:pharmacy.seller.delete')
            ->name('sellers.destroy');

        Route::post('/sellers/{id}/impersonate', [\Src\Infrastructure\Pharmacy\Http\Controllers\SellerController::class, 'impersonate'])
            ->middleware('permission:pharmacy.seller.edit')
            ->name('sellers.impersonate');

        // Dépôts - Liste et création
        Route::get('/depots', [\App\Http\Controllers\DepotController::class, 'index'])
            ->middleware('permission:pharmacy.seller.view')
            ->name('depots.index');
        Route::post('/depots', [\App\Http\Controllers\DepotController::class, 'store'])
            ->middleware('permission:pharmacy.seller.create')
            ->name('depots.store');

        Route::post('/products/{id}/stock', [\Src\Infrastructure\Pharmacy\Http\Controllers\ProductController::class, 'updateStock'])
            ->middleware('permission:pharmacy.pharmacy.product.manage|pharmacy.product.manage|stock.adjust')
            ->name('products.stock.update');

        // Categories - Permissions granulaires strictes
        Route::get('/categories', [\Src\Infrastructure\Pharmacy\Http\Controllers\CategoryController::class, 'index'])
            ->middleware('permission:pharmacy.category.view')
            ->name('categories.index');
        
        Route::post('/categories', [\Src\Infrastructure\Pharmacy\Http\Controllers\CategoryController::class, 'store'])
            ->middleware('permission:pharmacy.category.create')
            ->name('categories.store');
        
        Route::put('/categories/{id}', [\Src\Infrastructure\Pharmacy\Http\Controllers\CategoryController::class, 'update'])
            ->middleware('permission:pharmacy.category.update')
            ->name('categories.update');
        
        Route::delete('/categories/{id}', [\Src\Infrastructure\Pharmacy\Http\Controllers\CategoryController::class, 'destroy'])
            ->middleware('permission:pharmacy.category.delete')
            ->name('categories.destroy');
        
        Route::get('/categories/export/pdf', [\Src\Infrastructure\Pharmacy\Http\Controllers\CategoryController::class, 'exportPdf'])
            ->middleware('permission:pharmacy.category.view')
            ->name('categories.export.pdf');

        // Batches & Expirations (Lots et Dates d'expiration)
        Route::get('/expirations', [\Src\Infrastructure\Pharmacy\Http\Controllers\BatchController::class, 'index'])
            ->middleware('permission:pharmacy.expiration.view|pharmacy.batch.view')
            ->name('expirations.index');
        Route::get('/batches/summary', [\Src\Infrastructure\Pharmacy\Http\Controllers\BatchController::class, 'summary'])
            ->middleware('permission:pharmacy.expiration.view|pharmacy.batch.view')
            ->name('batches.summary');
        Route::get('/batches/expired', [\Src\Infrastructure\Pharmacy\Http\Controllers\BatchController::class, 'expired'])
            ->middleware('permission:pharmacy.expiration.view|pharmacy.batch.view')
            ->name('batches.expired');
        Route::get('/batches/expiring', [\Src\Infrastructure\Pharmacy\Http\Controllers\BatchController::class, 'expiring'])
            ->middleware('permission:pharmacy.expiration.view|pharmacy.batch.view')
            ->name('batches.expiring');
        Route::post('/batches', [\Src\Infrastructure\Pharmacy\Http\Controllers\BatchController::class, 'store'])
            ->middleware('permission:pharmacy.batch.manage')
            ->name('batches.store');
        Route::get('/products/{productId}/batches', [\Src\Infrastructure\Pharmacy\Http\Controllers\BatchController::class, 'getProductBatches'])
            ->middleware('permission:pharmacy.batch.view')
            ->name('products.batches');
        Route::delete('/batches/{id}', [\Src\Infrastructure\Pharmacy\Http\Controllers\BatchController::class, 'destroy'])
            ->middleware('permission:pharmacy.batch.manage')
            ->name('batches.destroy');

        // ========== EXPORTS (PDF & Excel) ==========
        Route::prefix('exports')->name('exports.')->group(function () {
            // Stock
            Route::get('/stock/pdf', [\Src\Infrastructure\Pharmacy\Http\Controllers\ExportController::class, 'stockPdf'])
                ->middleware('permission:stock.view')
                ->name('stock.pdf');
            Route::get('/stock/excel', [\Src\Infrastructure\Pharmacy\Http\Controllers\ExportController::class, 'stockExcel'])
                ->middleware('permission:stock.view')
                ->name('stock.excel');

            // Ventes
            Route::get('/sales/pdf', [\Src\Infrastructure\Pharmacy\Http\Controllers\ExportController::class, 'salesPdf'])
                ->middleware('permission:pharmacy.sales.view')
                ->name('sales.pdf');
            Route::get('/sales/excel', [\Src\Infrastructure\Pharmacy\Http\Controllers\ExportController::class, 'salesExcel'])
                ->middleware('permission:pharmacy.sales.view')
                ->name('sales.excel');

            // Achats
            Route::get('/purchases/pdf', [\Src\Infrastructure\Pharmacy\Http\Controllers\ExportController::class, 'purchasesPdf'])
                ->middleware('permission:pharmacy.purchases.view')
                ->name('purchases.pdf');
            Route::get('/purchases/excel', [\Src\Infrastructure\Pharmacy\Http\Controllers\ExportController::class, 'purchasesExcel'])
                ->middleware('permission:pharmacy.purchases.view')
                ->name('purchases.excel');

            // Fournisseurs
            Route::get('/suppliers/pdf', [\Src\Infrastructure\Pharmacy\Http\Controllers\ExportController::class, 'suppliersPdf'])
                ->middleware('permission:pharmacy.supplier.view')
                ->name('suppliers.pdf');
            Route::get('/suppliers/excel', [\Src\Infrastructure\Pharmacy\Http\Controllers\ExportController::class, 'suppliersExcel'])
                ->middleware('permission:pharmacy.supplier.view')
                ->name('suppliers.excel');

            // Clients
            Route::get('/customers/pdf', [\Src\Infrastructure\Pharmacy\Http\Controllers\ExportController::class, 'customersPdf'])
                ->middleware('permission:pharmacy.customer.view')
                ->name('customers.pdf');
            Route::get('/customers/excel', [\Src\Infrastructure\Pharmacy\Http\Controllers\ExportController::class, 'customersExcel'])
                ->middleware('permission:pharmacy.customer.view')
                ->name('customers.excel');

            // Expirations
            Route::get('/expirations/pdf', [\Src\Infrastructure\Pharmacy\Http\Controllers\ExportController::class, 'expirationsPdf'])
                ->middleware('permission:pharmacy.expiration.view|pharmacy.batch.view')
                ->name('expirations.pdf');
            Route::get('/expirations/excel', [\Src\Infrastructure\Pharmacy\Http\Controllers\ExportController::class, 'expirationsExcel'])
                ->middleware('permission:pharmacy.expiration.view|pharmacy.batch.view')
                ->name('expirations.excel');

            // Mouvements de stock
            Route::get('/movements/pdf', [\Src\Infrastructure\Pharmacy\Http\Controllers\ExportController::class, 'movementsPdf'])
                ->middleware('permission:stock.movement.view')
                ->name('movements.pdf');
            Route::get('/movements/excel', [\Src\Infrastructure\Pharmacy\Http\Controllers\ExportController::class, 'movementsExcel'])
                ->middleware('permission:stock.movement.view')
                ->name('movements.excel');
        });

        // ========== TRANSFERTS INTER-MAGASINS ==========
        Route::get('/transfers', [\Src\Infrastructure\Pharmacy\Http\Controllers\StockTransferController::class, 'index'])
            ->middleware('permission:transfer.view')
            ->name('transfers.index');
        Route::get('/transfers/create', [\Src\Infrastructure\Pharmacy\Http\Controllers\StockTransferController::class, 'create'])
            ->middleware('permission:transfer.create')
            ->name('transfers.create');
        Route::post('/transfers', [\Src\Infrastructure\Pharmacy\Http\Controllers\StockTransferController::class, 'store'])
            ->middleware('permission:transfer.create')
            ->name('transfers.store');
        Route::get('/transfers/{id}', [\Src\Infrastructure\Pharmacy\Http\Controllers\StockTransferController::class, 'show'])
            ->middleware('permission:transfer.view')
            ->name('transfers.show');
        Route::post('/transfers/{id}/items', [\Src\Infrastructure\Pharmacy\Http\Controllers\StockTransferController::class, 'addItem'])
            ->middleware('permission:transfer.create')
            ->name('transfers.items.add');
        Route::put('/transfers/{id}/items/{itemId}', [\Src\Infrastructure\Pharmacy\Http\Controllers\StockTransferController::class, 'updateItem'])
            ->middleware('permission:transfer.create')
            ->name('transfers.items.update');
        Route::delete('/transfers/{id}/items/{itemId}', [\Src\Infrastructure\Pharmacy\Http\Controllers\StockTransferController::class, 'removeItem'])
            ->middleware('permission:transfer.create')
            ->name('transfers.items.remove');
        Route::post('/transfers/{id}/validate', [\Src\Infrastructure\Pharmacy\Http\Controllers\StockTransferController::class, 'validate'])
            ->middleware('permission:transfer.validate')
            ->name('transfers.validate');
        Route::post('/transfers/{id}/cancel', [\Src\Infrastructure\Pharmacy\Http\Controllers\StockTransferController::class, 'cancel'])
            ->middleware('permission:transfer.cancel')
            ->name('transfers.cancel');
        Route::get('/transfers/{id}/pdf', [\Src\Infrastructure\Pharmacy\Http\Controllers\StockTransferController::class, 'exportPdf'])
            ->middleware('permission:transfer.print')
            ->name('transfers.pdf');

        // ========== API Mouvements de Stock (Historique Produits) ==========
        Route::prefix('api')->group(function () {
            // Liste des mouvements avec filtres
            Route::get('/product-movements', [\Src\Infrastructure\Pharmacy\Http\Controllers\ProductMovementController::class, 'index'])
                ->middleware('permission:stock.movement.view')
                ->name('api.product-movements.index');
            
            // Export PDF global des mouvements (DOIT être avant la route avec {id})
            Route::get('/product-movements/export/pdf', [\Src\Infrastructure\Pharmacy\Http\Controllers\ProductMovementController::class, 'exportGlobalPdf'])
                ->middleware('permission:stock.movement.print')
                ->name('api.product-movements.pdf.global');
            
            // Export PDF d'un mouvement individuel
            Route::get('/product-movements/{id}/pdf', [\Src\Infrastructure\Pharmacy\Http\Controllers\ProductMovementController::class, 'exportSinglePdf'])
                ->middleware('permission:stock.movement.print')
                ->name('api.product-movements.pdf.single');
        });
    });
});

/**
 * Categories Routes
 */
/*Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/categories', [\App\Http\Controllers\CategoryController::class, 'index'])
        ->middleware('permission:categories.view')
        ->name('categories.index');
    
    Route::post('/categories', [\App\Http\Controllers\CategoryController::class, 'store'])
        ->middleware('permission:categories.create')
        ->name('categories.store');
    
    Route::put('/categories/{category}', [\App\Http\Controllers\CategoryController::class, 'update'])
        ->middleware('permission:categories.update')
        ->name('categories.update');
    
    Route::delete('/categories/{category}', [\App\Http\Controllers\CategoryController::class, 'destroy'])
        ->middleware('permission:categories.delete')
        ->name('categories.destroy');
});*/

/**
 * Settings Routes - Store Settings (Paramètres boutique)
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/settings', [\Src\Infrastructure\Settings\Http\Controllers\SettingsController::class, 'index'])
        ->middleware('permission:settings.view')
        ->name('settings.index');

    Route::put('/settings', [\Src\Infrastructure\Settings\Http\Controllers\SettingsController::class, 'update'])
        ->middleware('permission:settings.update')
        ->name('settings.update');

    // Currency Management Routes (DDD Architecture)
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/currencies', [\Src\Infrastructure\Currency\Http\Controllers\CurrencyController::class, 'index'])
            ->middleware('permission:settings.currency.view|settings.settings.currency.view')
            ->name('currencies');
        
        Route::post('/currencies', [\Src\Infrastructure\Currency\Http\Controllers\CurrencyController::class, 'store'])
            ->middleware('permission:settings.currency.create|settings.settings.currency.create')
            ->name('currencies.store');
        
        Route::put('/currencies/{currency}', [\Src\Infrastructure\Currency\Http\Controllers\CurrencyController::class, 'update'])
            ->middleware('permission:settings.currency.update|settings.settings.currency.update')
            ->name('currencies.update');
        
        Route::delete('/currencies/{currency}', [\Src\Infrastructure\Currency\Http\Controllers\CurrencyController::class, 'destroy'])
            ->middleware('permission:settings.currency.delete|settings.settings.currency.delete')
            ->name('currencies.destroy');
        
        Route::post('/exchange-rates', [\Src\Infrastructure\Currency\Http\Controllers\CurrencyController::class, 'storeExchangeRate'])
            ->middleware('permission:settings.currency.update|settings.settings.currency.update')
            ->name('exchange-rates.store');
        
        Route::put('/exchange-rates/{exchangeRate}', [\Src\Infrastructure\Currency\Http\Controllers\CurrencyController::class, 'updateExchangeRate'])
            ->middleware('permission:settings.currency.update|settings.settings.currency.update')
            ->name('exchange-rates.update');
        
        Route::delete('/exchange-rates/{exchangeRate}', [\Src\Infrastructure\Currency\Http\Controllers\CurrencyController::class, 'destroyExchangeRate'])
            ->middleware('permission:settings.currency.update|settings.settings.currency.update')
            ->name('exchange-rates.destroy');
    });
});

/**
 * Settings Routes - Legacy (commented)
 */
/*Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/company', [\App\Http\Controllers\SettingsController::class, 'company'])
            ->middleware('permission:settings.view')
            ->name('company');
        
        Route::post('/company', [\App\Http\Controllers\SettingsController::class, 'updateCompany'])
            ->middleware('permission:settings.view')
            ->name('company.update');
        
        Route::get('/currencies', [\App\Http\Controllers\Settings\CurrencyController::class, 'index'])
            ->middleware('permission:settings.currency.view')
            ->name('currencies');
        
        Route::post('/currencies', [\App\Http\Controllers\Settings\CurrencyController::class, 'store'])
            ->middleware('permission:settings.currency.create')
            ->name('currencies.store');
        
        Route::put('/currencies/{currency}', [\App\Http\Controllers\Settings\CurrencyController::class, 'update'])
            ->middleware('permission:settings.currency.update')
            ->name('currencies.update');
        
        Route::delete('/currencies/{currency}', [\App\Http\Controllers\Settings\CurrencyController::class, 'destroy'])
            ->middleware('permission:settings.currency.delete')
            ->name('currencies.destroy');
        
        Route::post('/exchange-rates', [\App\Http\Controllers\Settings\CurrencyController::class, 'storeExchangeRate'])
            ->middleware('permission:settings.currency.update')
            ->name('exchange-rates.store');
        
        Route::put('/exchange-rates/{exchangeRate}', [\App\Http\Controllers\Settings\CurrencyController::class, 'updateExchangeRate'])
            ->middleware('permission:settings.currency.update')
            ->name('exchange-rates.update');
        
        Route::delete('/exchange-rates/{exchangeRate}', [\App\Http\Controllers\Settings\CurrencyController::class, 'destroyExchangeRate'])
            ->middleware('permission:settings.currency.update')
            ->name('exchange-rates.destroy');
    });
});*/

require __DIR__.'/auth.php';

/**
 * Onboarding Routes
 */
require __DIR__.'/onboarding.php';
