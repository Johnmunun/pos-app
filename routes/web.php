<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
});

/**
 * Access Manager Routes - RBAC (Rôles & Permissions)
 * Toutes les routes sont protégées par permissions spécifiques
 */
Route::middleware(['auth', 'verified', 'root'])->group(function () {
    // Gestion des rôles
    Route::get('/admin/access/roles', [\App\Http\Controllers\Admin\AccessManagerController::class, 'roles'])
        ->middleware('permission:access.roles.view')
        ->name('admin.access.roles');
    
    Route::get('/admin/access/roles/{id}', [\App\Http\Controllers\Admin\AccessManagerController::class, 'getRole'])
        ->middleware('permission:access.roles.update')
        ->name('admin.access.roles.get');
    
    Route::post('/admin/access/roles', [\App\Http\Controllers\Admin\AccessManagerController::class, 'createRole'])
        ->middleware('permission:access.roles.create')
        ->name('admin.access.roles.store');
    
    Route::put('/admin/access/roles/{id}', [\App\Http\Controllers\Admin\AccessManagerController::class, 'updateRole'])
        ->middleware('permission:access.roles.update')
        ->name('admin.access.roles.update');
    
    Route::delete('/admin/access/roles/{id}', [\App\Http\Controllers\Admin\AccessManagerController::class, 'deleteRole'])
        ->middleware('permission:access.roles.delete')
        ->name('admin.access.roles.delete');

    // Gestion des permissions
    Route::get('/admin/access/permissions', [\App\Http\Controllers\Admin\AccessManagerController::class, 'permissions'])
        ->middleware('permission:access.permissions.view')
        ->name('admin.access.permissions');
    
    Route::delete('/admin/access/permissions/{id}', [\App\Http\Controllers\Admin\AccessManagerController::class, 'deletePermission'])
        ->middleware('permission:access.permissions.delete')
        ->name('admin.access.permissions.delete');
    
    Route::post('/admin/access/permissions/sync', [\App\Http\Controllers\Admin\AccessManagerController::class, 'syncPermissions'])
        ->middleware('permission:access.permissions.sync')
        ->name('admin.access.permissions.sync');
});

/**
 * Pharmacy Module Routes
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('pharmacy')->name('pharmacy.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Pharmacy\PharmacyController::class, 'dashboard'])
            ->middleware('permission:module.pharmacy')
            ->name('dashboard');

        // Products
        Route::get('/products', [\App\Http\Controllers\Pharmacy\PharmacyController::class, 'products'])
            ->middleware('permission:pharmacy.product.manage')
            ->name('products');
        
        Route::get('/products/create', [\App\Http\Controllers\Pharmacy\PharmacyController::class, 'createProduct'])
            ->middleware('permission:pharmacy.product.manage')
            ->name('products.create');
        
        Route::post('/products', [\App\Http\Controllers\Pharmacy\PharmacyController::class, 'storeProduct'])
            ->middleware('permission:pharmacy.product.manage')
            ->name('products.store');
        
        Route::get('/products/{product}/edit', [\App\Http\Controllers\Pharmacy\PharmacyController::class, 'editProduct'])
            ->middleware('permission:pharmacy.product.manage')
            ->name('products.edit');
        
        Route::put('/products/{product}', [\App\Http\Controllers\Pharmacy\PharmacyController::class, 'updateProduct'])
            ->middleware('permission:pharmacy.product.manage')
            ->name('products.update');
        
        Route::delete('/products/{product}', [\App\Http\Controllers\Pharmacy\PharmacyController::class, 'destroyProduct'])
            ->middleware('permission:pharmacy.product.manage')
            ->name('products.destroy');

        // Batches
        Route::post('/products/{product}/batches', [\App\Http\Controllers\Pharmacy\PharmacyController::class, 'storeBatch'])
            ->middleware('permission:pharmacy.product.manage')
            ->name('products.batches.store');
        
        Route::put('/products/{product}/batches/{batch}', [\App\Http\Controllers\Pharmacy\PharmacyController::class, 'updateBatch'])
            ->middleware('permission:pharmacy.product.manage')
            ->name('products.batches.update');
        
        Route::delete('/products/{product}/batches/{batch}', [\App\Http\Controllers\Pharmacy\PharmacyController::class, 'destroyBatch'])
            ->middleware('permission:pharmacy.product.manage')
            ->name('products.batches.destroy');

        // Stock
        Route::get('/stock', [\App\Http\Controllers\Pharmacy\PharmacyController::class, 'stock'])
            ->middleware('permission:pharmacy.stock.manage')
            ->name('stock');

        // Sales
        Route::get('/sales', [\App\Http\Controllers\Pharmacy\SalesController::class, 'index'])
            ->middleware('permission:pharmacy.sale.create')
            ->name('sales');
        Route::post('/sales', [\App\Http\Controllers\Pharmacy\SalesController::class, 'store'])
            ->middleware('permission:pharmacy.sale.create')
            ->name('sales.store');

        // Expiry
        Route::get('/expiry', [\App\Http\Controllers\Pharmacy\PharmacyController::class, 'expiry'])
            ->middleware('permission:pharmacy.expiry.view')
            ->name('expiry');

        // Reports
        Route::get('/reports', [\App\Http\Controllers\Pharmacy\PharmacyController::class, 'reports'])
            ->middleware('permission:pharmacy.report.view')
            ->name('reports');
    });
});

/**
 * Categories Routes
 */
Route::middleware(['auth', 'verified'])->group(function () {
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
});

/**
 * Settings Routes
 */
Route::middleware(['auth', 'verified'])->group(function () {
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
});

require __DIR__.'/auth.php';
