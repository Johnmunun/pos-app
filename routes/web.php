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
        // Dashboard
        Route::get('/dashboard', [\Src\Infrastructure\Pharmacy\Http\Controllers\ProductController::class, 'index'])
            ->middleware('permission:module.pharmacy')
            ->name('dashboard');

        // Products
        // Support multiple permission formats for compatibility
        Route::get('/products', [\Src\Infrastructure\Pharmacy\Http\Controllers\ProductController::class, 'index'])
            ->middleware('permission:pharmacy.pharmacy.product.manage|pharmacy.product.manage')
            ->name('products');
        
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
        Route::post('/products/{id}/stock', [\Src\Infrastructure\Pharmacy\Http\Controllers\ProductController::class, 'updateStock'])
            ->middleware('permission:pharmacy.pharmacy.product.manage|pharmacy.product.manage')
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
