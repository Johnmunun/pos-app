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

require __DIR__.'/auth.php';
