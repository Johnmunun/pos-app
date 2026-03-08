<?php

use Illuminate\Support\Facades\Route;
use Src\Infrastructure\Ecommerce\Http\Controllers\OrderController;
use Src\Infrastructure\Ecommerce\Http\Controllers\CustomerController;
use Src\Infrastructure\Ecommerce\Http\Controllers\CatalogController;
use Src\Infrastructure\Ecommerce\Http\Controllers\DashboardController;

/**
 * Module Ecommerce - Vente en ligne
 */
Route::prefix('ecommerce')
    ->as('ecommerce.')
    ->middleware(['auth', 'verified', 'permission:module.ecommerce'])
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('permission:ecommerce.dashboard.view|module.ecommerce')
            ->name('dashboard');

        // Catalogue
        Route::get('/catalog', [CatalogController::class, 'index'])
            ->middleware('permission:ecommerce.catalog.view|ecommerce.view|module.ecommerce')
            ->name('catalog.index');
        Route::get('/catalog/{id}', [CatalogController::class, 'show'])
            ->middleware('permission:ecommerce.catalog.view|ecommerce.view|module.ecommerce')
            ->name('catalog.show');

        // Produits (placeholder - à implémenter)
        Route::get('/products', function () {
            return \Inertia\Inertia::render('Ecommerce/Products/Index', [
                'message' => 'Page en cours de développement'
            ]);
        })
            ->middleware('permission:ecommerce.product.view|module.ecommerce')
            ->name('products.index');

        // Catégories (placeholder - à implémenter)
        Route::get('/categories', function () {
            return \Inertia\Inertia::render('Ecommerce/Categories/Index', [
                'message' => 'Page en cours de développement'
            ]);
        })
            ->middleware('permission:ecommerce.category.view|module.ecommerce')
            ->name('categories.index');

        // Panier
        Route::get('/cart', function () {
            return \Inertia\Inertia::render('Ecommerce/Cart/Index');
        })
            ->middleware('permission:ecommerce.cart.view|ecommerce.view|module.ecommerce')
            ->name('cart.index');

        // Commandes
        Route::get('/orders', [OrderController::class, 'index'])
            ->middleware('permission:ecommerce.order.view|ecommerce.view|module.ecommerce')
            ->name('orders.index');
        Route::get('/orders/{id}', [OrderController::class, 'show'])
            ->middleware('permission:ecommerce.order.view|ecommerce.view|module.ecommerce')
            ->name('orders.show');
        Route::post('/orders', [OrderController::class, 'store'])
            ->middleware('permission:ecommerce.order.create|ecommerce.create|module.ecommerce')
            ->name('orders.store');
        Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus'])
            ->middleware('permission:ecommerce.order.status.update|ecommerce.order.update|module.ecommerce')
            ->name('orders.update-status');
        Route::put('/orders/{id}/payment-status', [OrderController::class, 'updatePaymentStatus'])
            ->middleware('permission:ecommerce.order.payment.update|ecommerce.order.update|module.ecommerce')
            ->name('orders.update-payment-status');
        Route::delete('/orders/{id}', [OrderController::class, 'destroy'])
            ->middleware('permission:ecommerce.order.delete|ecommerce.delete|module.ecommerce')
            ->name('orders.destroy');

        // Clients
        Route::get('/customers', [CustomerController::class, 'index'])
            ->middleware('permission:ecommerce.customer.view|ecommerce.view|module.ecommerce')
            ->name('customers.index');
        Route::post('/customers', [CustomerController::class, 'store'])
            ->middleware('permission:ecommerce.customer.create|ecommerce.create|module.ecommerce')
            ->name('customers.store');

        // Paiements (placeholder - à implémenter)
        Route::get('/payments', function () {
            return \Inertia\Inertia::render('Ecommerce/Payments/Index', [
                'message' => 'Page en cours de développement'
            ]);
        })
            ->middleware('permission:ecommerce.payment.view|module.ecommerce')
            ->name('payments.index');

        // Livraisons (placeholder - à implémenter)
        Route::get('/shipping', function () {
            return \Inertia\Inertia::render('Ecommerce/Shipping/Index', [
                'message' => 'Page en cours de développement'
            ]);
        })
            ->middleware('permission:ecommerce.shipping.view|module.ecommerce')
            ->name('shipping.index');

        // Promotions (placeholder - à implémenter)
        Route::get('/promotions', function () {
            return \Inertia\Inertia::render('Ecommerce/Promotions/Index', [
                'message' => 'Page en cours de développement'
            ]);
        })
            ->middleware('permission:ecommerce.promotion.view|module.ecommerce')
            ->name('promotions.index');

        // Coupons (placeholder - à implémenter)
        Route::get('/coupons', function () {
            return \Inertia\Inertia::render('Ecommerce/Coupons/Index', [
                'message' => 'Page en cours de développement'
            ]);
        })
            ->middleware('permission:ecommerce.coupon.view|module.ecommerce')
            ->name('coupons.index');

        // Avis (placeholder - à implémenter)
        Route::get('/reviews', function () {
            return \Inertia\Inertia::render('Ecommerce/Reviews/Index', [
                'message' => 'Page en cours de développement'
            ]);
        })
            ->middleware('permission:ecommerce.review.view|module.ecommerce')
            ->name('reviews.index');

        // Stock (placeholder - à implémenter)
        Route::get('/stock', function () {
            return \Inertia\Inertia::render('Ecommerce/Stock/Index', [
                'message' => 'Page en cours de développement'
            ]);
        })
            ->middleware('permission:ecommerce.stock.view|module.ecommerce')
            ->name('stock.index');

        // Rapports (placeholder - à implémenter)
        Route::get('/reports', function () {
            return \Inertia\Inertia::render('Ecommerce/Reports/Index', [
                'message' => 'Page en cours de développement'
            ]);
        })
            ->middleware('permission:ecommerce.report.view|ecommerce.analytics.view|module.ecommerce')
            ->name('reports.index');

        // Paramètres (placeholder - à implémenter)
        Route::get('/settings', function () {
            return \Inertia\Inertia::render('Ecommerce/Settings/Index', [
                'message' => 'Page en cours de développement'
            ]);
        })
            ->middleware('permission:ecommerce.settings.view|module.ecommerce')
            ->name('settings.index');
    });
