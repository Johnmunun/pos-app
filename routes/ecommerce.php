<?php

use Illuminate\Support\Facades\Route;
use Src\Infrastructure\Ecommerce\Http\Controllers\OrderController;
use Src\Infrastructure\Ecommerce\Http\Controllers\CustomerController;
use Src\Infrastructure\Ecommerce\Http\Controllers\CatalogController;
use Src\Infrastructure\Ecommerce\Http\Controllers\DashboardController;
use Src\Infrastructure\Ecommerce\Http\Controllers\DownloadController;
use Src\Infrastructure\Ecommerce\Http\Controllers\ProductController;
use Src\Infrastructure\Ecommerce\Http\Controllers\CategoryController;
use Src\Infrastructure\Ecommerce\Http\Controllers\ShippingMethodController;
use Src\Infrastructure\Ecommerce\Http\Controllers\PaymentMethodController;
use Src\Infrastructure\Ecommerce\Http\Controllers\PaymentSuccessController;
use Src\Infrastructure\Ecommerce\Http\Controllers\PromotionController;
use Src\Infrastructure\Ecommerce\Http\Controllers\CouponController;
use Src\Infrastructure\Ecommerce\Http\Controllers\CartController;
use Src\Infrastructure\Ecommerce\Http\Controllers\CheckoutController;
use Src\Infrastructure\Ecommerce\Http\Controllers\ReviewController;
use Src\Infrastructure\Ecommerce\Http\Controllers\StockController;
use Src\Infrastructure\Ecommerce\Http\Controllers\ReportController;
use Src\Infrastructure\Ecommerce\Http\Controllers\SettingsController;
use Src\Infrastructure\Ecommerce\Http\Controllers\StorefrontController;
use Src\Infrastructure\Ecommerce\Http\Controllers\CmsPageController;
use Src\Infrastructure\Ecommerce\Http\Controllers\CmsBannerController;
use Src\Infrastructure\Ecommerce\Http\Controllers\CmsBlogController;
use Src\Infrastructure\Ecommerce\Http\Controllers\CmsBlogCategoryController;
use Src\Infrastructure\Ecommerce\Http\Controllers\CmsMediaController;
use Src\Infrastructure\Ecommerce\Http\Controllers\MarketingController;
use Src\Infrastructure\Ecommerce\Http\Controllers\EcommerceMarketingAiController;
use Src\Infrastructure\Ecommerce\Http\Controllers\StorefrontVisitController;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcProductController;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcCategoryController;
use Src\Infrastructure\Ecommerce\Http\Controllers\SupplierController;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcSupplierController;
use Src\Infrastructure\GlobalCommerce\Http\Controllers\GcExportController;
use Src\Infrastructure\Ecommerce\Http\Controllers\ExportController as EcommerceExportController;

/**
 * Téléchargement produit digital (lien public sécurisé par token)
 */
Route::get('/ecommerce/download/{token}', DownloadController::class)
    ->name('ecommerce.download');

/**
 * Page succès paiement (produit digital)
 * - token = token de téléchargement sécurisé
 */
Route::get('/ecommerce/payment/success/{token}', [PaymentSuccessController::class, 'show'])
    ->name('ecommerce.payment.success');

/**
 * Module Ecommerce - Vente en ligne
 */
Route::prefix('ecommerce')
    ->as('ecommerce.')
    ->middleware(['auth', 'verified', 'permission:module.ecommerce', 'feature.enabled:ecommerce.module'])
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('permission:ecommerce.dashboard.view|module.ecommerce')
            ->name('dashboard');

        // Vitrine (storefront) - vue clients / aperçu admin
        Route::get('/storefront', [StorefrontController::class, 'index'])
            ->middleware('permission:ecommerce.catalog.view|ecommerce.view|module.ecommerce')
            ->name('storefront.index');
        Route::get('/storefront/page/{slug}', [StorefrontController::class, 'showPage'])
            ->middleware('permission:ecommerce.catalog.view|ecommerce.view|module.ecommerce')
            ->name('storefront.page');
        Route::get('/storefront/blog', [StorefrontController::class, 'blog'])
            ->middleware('permission:ecommerce.catalog.view|ecommerce.view|module.ecommerce')
            ->name('storefront.blog');
        Route::get('/storefront/blog/{slug}', [StorefrontController::class, 'blogShow'])
            ->middleware('permission:ecommerce.catalog.view|ecommerce.view|module.ecommerce')
            ->name('storefront.blog.show');
        Route::get('/storefront/catalog', [StorefrontController::class, 'catalog'])
            ->middleware('permission:ecommerce.catalog.view|ecommerce.view|module.ecommerce')
            ->name('storefront.catalog');
        Route::get('/storefront/product/{id}', [StorefrontController::class, 'showProduct'])
            ->middleware('permission:ecommerce.catalog.view|ecommerce.view|module.ecommerce')
            ->name('storefront.product');
        Route::get('/storefront/cart', [StorefrontController::class, 'cart'])
            ->middleware('permission:ecommerce.cart.view|ecommerce.view|module.ecommerce')
            ->name('storefront.cart');
        Route::post('/storefront/switch-shop', [StorefrontController::class, 'switchShop'])
            ->middleware('permission:ecommerce.catalog.view|ecommerce.view|module.ecommerce')
            ->name('storefront.switch-shop');

        // Catalogue
        Route::get('/catalog', [CatalogController::class, 'index'])
            ->middleware('permission:ecommerce.catalog.view|ecommerce.view|module.ecommerce')
            ->name('catalog.index');
        Route::get('/catalog/{id}', [CatalogController::class, 'show'])
            ->middleware('permission:ecommerce.catalog.view|ecommerce.view|module.ecommerce')
            ->name('catalog.show');

        // Produits
        Route::get('/products', [ProductController::class, 'index'])
            ->middleware('permission:ecommerce.product.view|module.ecommerce')
            ->name('products.index');
        Route::post('/products', [ProductController::class, 'store'])
            ->middleware('permission:ecommerce.product.create|ecommerce.product.manage|module.ecommerce')
            ->name('products.store');
        Route::put('/products/{id}', [ProductController::class, 'update'])
            ->middleware('permission:ecommerce.product.update|ecommerce.product.manage|module.ecommerce')
            ->name('products.update');
        Route::post('/products/{id}/toggle-status', [GcProductController::class, 'toggleStatus'])
            ->middleware('permission:ecommerce.product.update|ecommerce.product.manage|module.ecommerce')
            ->name('products.toggle-status');
        Route::post('/products/{id}/toggle-publish', [GcProductController::class, 'toggleEcommercePublish'])
            ->middleware('permission:ecommerce.product.update|ecommerce.product.manage|module.ecommerce')
            ->name('products.toggle-publish');
        Route::delete('/products/{id}', [ProductController::class, 'destroy'])
            ->middleware('permission:ecommerce.product.delete|ecommerce.product.manage|module.ecommerce')
            ->name('products.destroy');

        // Catégories
        Route::get('/categories', [CategoryController::class, 'index'])
            ->middleware('permission:ecommerce.category.view|module.ecommerce')
            ->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])
            ->middleware('permission:ecommerce.category.create|ecommerce.category.manage|module.ecommerce')
            ->name('categories.store');
        Route::put('/categories/{id}', [CategoryController::class, 'update'])
            ->middleware('permission:ecommerce.category.update|ecommerce.category.manage|module.ecommerce')
            ->name('categories.update');

        // Panier
        Route::get('/cart', [CartController::class, 'index'])
            ->middleware('permission:ecommerce.cart.view|ecommerce.view|module.ecommerce')
            ->name('cart.index');

        // Checkout (calcul livraison, validation coupon)
        Route::post('/checkout/calculate-shipping', [CheckoutController::class, 'calculateShipping'])
            ->middleware('permission:ecommerce.cart.view|ecommerce.view|module.ecommerce')
            ->name('checkout.calculate-shipping');
        Route::post('/checkout/validate-coupon', [CheckoutController::class, 'validateCoupon'])
            ->middleware(['permission:ecommerce.cart.view|ecommerce.view|module.ecommerce', 'feature.enabled:ecommerce.promotions'])
            ->name('checkout.validate-coupon');

        // Commandes (plafond ventes mensuel via billing sales.monthly.max + FeatureLimitService)
        Route::get('/orders', [OrderController::class, 'index'])
            ->middleware(['permission:ecommerce.order.view|ecommerce.view|module.ecommerce', 'feature.enabled:ecommerce.orders'])
            ->name('orders.index');
        Route::get('/orders/{id}', [OrderController::class, 'show'])
            ->middleware(['permission:ecommerce.order.view|ecommerce.view|module.ecommerce', 'feature.enabled:ecommerce.orders'])
            ->name('orders.show');
        Route::post('/orders', [OrderController::class, 'store'])
            ->middleware(['permission:ecommerce.order.create|ecommerce.create|module.ecommerce', 'feature.enabled:ecommerce.orders'])
            ->name('orders.store');
        Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus'])
            ->middleware(['permission:ecommerce.order.status.update|ecommerce.order.update|module.ecommerce', 'feature.enabled:ecommerce.orders'])
            ->name('orders.update-status');
        Route::put('/orders/{id}/payment-status', [OrderController::class, 'updatePaymentStatus'])
            ->middleware(['permission:ecommerce.order.payment.update|ecommerce.order.update|module.ecommerce', 'feature.enabled:ecommerce.orders'])
            ->name('orders.update-payment-status');
        Route::delete('/orders/{id}', [OrderController::class, 'destroy'])
            ->middleware(['permission:ecommerce.order.delete|ecommerce.delete|module.ecommerce', 'feature.enabled:ecommerce.orders'])
            ->name('orders.destroy');

        // Clients
        Route::get('/customers', [CustomerController::class, 'index'])
            ->middleware('permission:ecommerce.customer.view|ecommerce.view|module.ecommerce')
            ->name('customers.index');
        Route::post('/customers', [CustomerController::class, 'store'])
            ->middleware('permission:ecommerce.customer.create|ecommerce.create|module.ecommerce')
            ->name('customers.store');
        Route::get('/customers/import/template', [CustomerController::class, 'importTemplate'])
            ->middleware('permission:ecommerce.customer.create|ecommerce.create|module.ecommerce')
            ->name('customers.import.template');
        Route::post('/customers/import/preview', [CustomerController::class, 'importPreview'])
            ->middleware('permission:ecommerce.customer.create|ecommerce.create|module.ecommerce')
            ->name('customers.import.preview');
        Route::post('/customers/import', [CustomerController::class, 'import'])
            ->middleware('permission:ecommerce.customer.create|ecommerce.create|module.ecommerce')
            ->name('customers.import');

        // Imports (Produits, Catégories - délégation GlobalCommerce)
        Route::get('/products/import/template', [GcProductController::class, 'importTemplate'])
            ->middleware('permission:ecommerce.product.view|module.ecommerce')
            ->name('products.import.template');
        Route::post('/products/import/preview', [GcProductController::class, 'importPreview'])
            ->middleware('permission:ecommerce.product.view|module.ecommerce')
            ->name('products.import.preview');
        Route::post('/products/import', [GcProductController::class, 'import'])
            ->middleware('permission:ecommerce.product.view|module.ecommerce')
            ->name('products.import');
        // Exports Produits (délégation GlobalCommerce)
        Route::get('/exports/products/pdf', [GcExportController::class, 'productsPdf'])
            ->middleware('permission:ecommerce.product.view|module.ecommerce')
            ->name('exports.products.pdf');
        Route::get('/exports/products/excel', [GcExportController::class, 'productsExcel'])
            ->middleware('permission:ecommerce.product.view|module.ecommerce')
            ->name('exports.products.excel');

        Route::get('/categories/import/template', [GcCategoryController::class, 'importTemplate'])
            ->middleware('permission:ecommerce.category.view|module.ecommerce')
            ->name('categories.import.template');
        Route::post('/categories/import/preview', [GcCategoryController::class, 'importPreview'])
            ->middleware('permission:ecommerce.category.view|module.ecommerce')
            ->name('categories.import.preview');
        Route::post('/categories/import', [GcCategoryController::class, 'import'])
            ->middleware('permission:ecommerce.category.view|module.ecommerce')
            ->name('categories.import');
        // Exports Catégories (délégation GlobalCommerce)
        Route::get('/exports/categories/pdf', [GcExportController::class, 'categoriesPdf'])
            ->middleware('permission:ecommerce.category.view|module.ecommerce')
            ->name('exports.categories.pdf');
        Route::get('/exports/categories/excel', [GcExportController::class, 'categoriesExcel'])
            ->middleware('permission:ecommerce.category.view|module.ecommerce')
            ->name('exports.categories.excel');

        // Fournisseurs (GlobalCommerce - données partagées)
        Route::get('/suppliers', [SupplierController::class, 'index'])
            ->middleware('permission:ecommerce.view|module.ecommerce')
            ->name('suppliers.index');
        Route::post('/suppliers', [SupplierController::class, 'store'])
            ->middleware('permission:ecommerce.view|module.ecommerce')
            ->name('suppliers.store');
        Route::get('/suppliers/import/template', [GcSupplierController::class, 'importTemplate'])
            ->middleware('permission:ecommerce.view|module.ecommerce')
            ->name('suppliers.import.template');
        Route::post('/suppliers/import/preview', [GcSupplierController::class, 'importPreview'])
            ->middleware('permission:ecommerce.view|module.ecommerce')
            ->name('suppliers.import.preview');
        Route::post('/suppliers/import', [GcSupplierController::class, 'import'])
            ->middleware('permission:ecommerce.view|module.ecommerce')
            ->name('suppliers.import');
        Route::put('/suppliers/{id}', [SupplierController::class, 'update'])
            ->middleware('permission:ecommerce.view|module.ecommerce')
            ->name('suppliers.update');
        Route::post('/suppliers/{id}/toggle-active', [SupplierController::class, 'toggleActive'])
            ->middleware('permission:ecommerce.view|module.ecommerce')
            ->name('suppliers.toggle-active');

        // Exports E-commerce (Clients, Ventes, Fournisseurs)
        Route::prefix('exports')->name('exports.')->group(function () {
            // Clients ecommerce
            Route::get('/customers/pdf', [EcommerceExportController::class, 'customersPdf'])
                ->middleware('permission:ecommerce.customer.view|ecommerce.view|module.ecommerce')
                ->name('customers.pdf');
            Route::get('/customers/excel', [EcommerceExportController::class, 'customersExcel'])
                ->middleware('permission:ecommerce.customer.view|ecommerce.view|module.ecommerce')
                ->name('customers.excel');

            // Ventes / commandes ecommerce
            Route::get('/orders/pdf', [EcommerceExportController::class, 'ordersPdf'])
                ->middleware(['permission:ecommerce.order.view|ecommerce.view|module.ecommerce', 'feature.enabled:ecommerce.orders'])
                ->name('orders.pdf');
            Route::get('/orders/excel', [EcommerceExportController::class, 'ordersExcel'])
                ->middleware(['permission:ecommerce.order.view|ecommerce.view|module.ecommerce', 'feature.enabled:ecommerce.orders'])
                ->name('orders.excel');

            // Fournisseurs (données partagées GlobalCommerce)
            Route::get('/suppliers/pdf', [GcExportController::class, 'suppliersPdf'])
                ->middleware('permission:ecommerce.view|module.ecommerce')
                ->name('suppliers.pdf');
            Route::get('/suppliers/excel', [GcExportController::class, 'suppliersExcel'])
                ->middleware('permission:ecommerce.view|module.ecommerce')
                ->name('suppliers.excel');
        });

        // Paiements
        Route::get('/payments', [PaymentMethodController::class, 'index'])
            ->middleware(['permission:ecommerce.payment.view|module.ecommerce', 'feature.enabled:api.payments'])
            ->name('payments.index');
        Route::get('/payments/create', [PaymentMethodController::class, 'create'])
            ->middleware(['permission:ecommerce.payment.view|module.ecommerce', 'feature.enabled:api.payments'])
            ->name('payments.create');
        Route::post('/payments', [PaymentMethodController::class, 'store'])
            ->middleware(['permission:ecommerce.payment.view|module.ecommerce', 'feature.enabled:api.payments'])
            ->name('payments.store');
        Route::get('/payments/{id}/edit', [PaymentMethodController::class, 'edit'])
            ->middleware(['permission:ecommerce.payment.view|module.ecommerce', 'feature.enabled:api.payments'])
            ->name('payments.edit');
        Route::put('/payments/{id}', [PaymentMethodController::class, 'update'])
            ->middleware(['permission:ecommerce.payment.view|module.ecommerce', 'feature.enabled:api.payments'])
            ->name('payments.update');
        Route::delete('/payments/{id}', [PaymentMethodController::class, 'destroy'])
            ->middleware(['permission:ecommerce.payment.view|module.ecommerce', 'feature.enabled:api.payments'])
            ->name('payments.destroy');

        // Livraisons
        Route::get('/shipping', [ShippingMethodController::class, 'index'])
            ->middleware('permission:ecommerce.shipping.view|module.ecommerce')
            ->name('shipping.index');
        Route::get('/shipping/create', [ShippingMethodController::class, 'create'])
            ->middleware('permission:ecommerce.shipping.view|module.ecommerce')
            ->name('shipping.create');
        Route::post('/shipping', [ShippingMethodController::class, 'store'])
            ->middleware('permission:ecommerce.shipping.view|module.ecommerce')
            ->name('shipping.store');
        Route::get('/shipping/{id}/edit', [ShippingMethodController::class, 'edit'])
            ->middleware('permission:ecommerce.shipping.view|module.ecommerce')
            ->name('shipping.edit');
        Route::put('/shipping/{id}', [ShippingMethodController::class, 'update'])
            ->middleware('permission:ecommerce.shipping.view|module.ecommerce')
            ->name('shipping.update');
        Route::delete('/shipping/{id}', [ShippingMethodController::class, 'destroy'])
            ->middleware('permission:ecommerce.shipping.view|module.ecommerce')
            ->name('shipping.destroy');

        // Promotions (plan Pro+)
        Route::get('/promotions', [PromotionController::class, 'index'])
            ->middleware(['permission:ecommerce.promotion.view|module.ecommerce', 'feature.enabled:ecommerce.promotions'])
            ->name('promotions.index');
        Route::get('/promotions/create', [PromotionController::class, 'create'])
            ->middleware(['permission:ecommerce.promotion.view|module.ecommerce', 'feature.enabled:ecommerce.promotions'])
            ->name('promotions.create');
        Route::post('/promotions', [PromotionController::class, 'store'])
            ->middleware(['permission:ecommerce.promotion.view|module.ecommerce', 'feature.enabled:ecommerce.promotions'])
            ->name('promotions.store');
        Route::get('/promotions/{id}/edit', [PromotionController::class, 'edit'])
            ->middleware(['permission:ecommerce.promotion.view|module.ecommerce', 'feature.enabled:ecommerce.promotions'])
            ->name('promotions.edit');
        Route::put('/promotions/{id}', [PromotionController::class, 'update'])
            ->middleware(['permission:ecommerce.promotion.view|module.ecommerce', 'feature.enabled:ecommerce.promotions'])
            ->name('promotions.update');
        Route::delete('/promotions/{id}', [PromotionController::class, 'destroy'])
            ->middleware(['permission:ecommerce.promotion.view|module.ecommerce', 'feature.enabled:ecommerce.promotions'])
            ->name('promotions.destroy');

        // Coupons (plan Pro+)
        Route::get('/coupons', [CouponController::class, 'index'])
            ->middleware(['permission:ecommerce.coupon.view|module.ecommerce', 'feature.enabled:ecommerce.promotions'])
            ->name('coupons.index');
        Route::get('/coupons/create', [CouponController::class, 'create'])
            ->middleware(['permission:ecommerce.coupon.view|module.ecommerce', 'feature.enabled:ecommerce.promotions'])
            ->name('coupons.create');
        Route::post('/coupons', [CouponController::class, 'store'])
            ->middleware(['permission:ecommerce.coupon.view|module.ecommerce', 'feature.enabled:ecommerce.promotions'])
            ->name('coupons.store');
        Route::get('/coupons/{id}/edit', [CouponController::class, 'edit'])
            ->middleware(['permission:ecommerce.coupon.view|module.ecommerce', 'feature.enabled:ecommerce.promotions'])
            ->name('coupons.edit');
        Route::put('/coupons/{id}', [CouponController::class, 'update'])
            ->middleware(['permission:ecommerce.coupon.view|module.ecommerce', 'feature.enabled:ecommerce.promotions'])
            ->name('coupons.update');
        Route::delete('/coupons/{id}', [CouponController::class, 'destroy'])
            ->middleware(['permission:ecommerce.coupon.view|module.ecommerce', 'feature.enabled:ecommerce.promotions'])
            ->name('coupons.destroy');

        // Avis
        Route::get('/reviews', [ReviewController::class, 'index'])
            ->middleware('permission:ecommerce.review.view|module.ecommerce')
            ->name('reviews.index');
        Route::get('/reviews/create', [ReviewController::class, 'create'])
            ->middleware('permission:ecommerce.review.view|module.ecommerce')
            ->name('reviews.create');
        Route::post('/reviews', [ReviewController::class, 'store'])
            ->middleware('permission:ecommerce.review.view|module.ecommerce')
            ->name('reviews.store');
        Route::get('/reviews/{id}/edit', [ReviewController::class, 'edit'])
            ->middleware('permission:ecommerce.review.view|module.ecommerce')
            ->name('reviews.edit');
        Route::put('/reviews/{id}', [ReviewController::class, 'update'])
            ->middleware('permission:ecommerce.review.view|module.ecommerce')
            ->name('reviews.update');
        Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])
            ->middleware('permission:ecommerce.review.view|module.ecommerce')
            ->name('reviews.destroy');

        // Stock
        Route::get('/stock', [StockController::class, 'index'])
            ->middleware('permission:ecommerce.stock.view|module.ecommerce')
            ->name('stock.index');

        // Compatibilite: ancien lien tuto e-commerce -> tuto global
        Route::get('/tutorial', function () {
            return redirect()->route('tutorial.index');
        })->name('tutorial.index');

        // Rapports
        Route::get('/reports', [ReportController::class, 'index'])
            ->middleware(['permission:ecommerce.report.view|ecommerce.analytics.view|module.ecommerce', 'feature.enabled:analytics.advanced'])
            ->name('reports.index');
        Route::get('/reports/export-sales-excel', [ReportController::class, 'exportSalesExcel'])
            ->middleware(['permission:ecommerce.report.view|ecommerce.analytics.view|module.ecommerce', 'feature.enabled:analytics.advanced'])
            ->name('reports.export-sales-excel');
        Route::get('/reports/export-sales-pdf', [ReportController::class, 'exportSalesPdf'])
            ->middleware(['permission:ecommerce.report.view|ecommerce.analytics.view|module.ecommerce', 'feature.enabled:analytics.advanced'])
            ->name('reports.export-sales-pdf');

        // Paramètres
        Route::get('/settings', [SettingsController::class, 'index'])
            ->middleware('permission:ecommerce.settings.view|module.ecommerce')
            ->name('settings.index');
        Route::put('/settings/domain', [SettingsController::class, 'updateDomain'])
            ->middleware('permission:ecommerce.settings.view|module.ecommerce')
            ->name('settings.domain.update');
        Route::put('/settings/storefront-shipping', [SettingsController::class, 'updateStorefrontShipping'])
            ->middleware('permission:ecommerce.settings.view|module.ecommerce')
            ->name('settings.storefront-shipping.update');

        // Marketing (SEO, Pixels, Tracking)
        Route::get('/marketing', [MarketingController::class, 'index'])
            ->middleware('permission:ecommerce.marketing.view|ecommerce.settings.view|module.ecommerce')
            ->name('marketing.index');
        Route::put('/marketing', [MarketingController::class, 'update'])
            ->middleware('permission:ecommerce.marketing.manage|ecommerce.settings.update|module.ecommerce')
            ->name('marketing.update');
        Route::post('/marketing/ai-suggest', EcommerceMarketingAiController::class)
            ->middleware([
                'permission:ecommerce.marketing.manage|ecommerce.settings.update|module.ecommerce',
                'feature.enabled:ecommerce.marketing.pro',
            ])
            ->name('marketing.ai-suggest');

        // CMS vitrine e-commerce
        Route::get('/storefront/cms', [StorefrontController::class, 'cms'])
            ->middleware('permission:ecommerce.settings.view|module.ecommerce')
            ->name('storefront.cms');
        Route::put('/storefront/cms', [StorefrontController::class, 'updateCms'])
            ->middleware('permission:ecommerce.settings.view|module.ecommerce')
            ->name('storefront.cms.update');

        // CMS - Pages
        Route::get('/cms/pages', [CmsPageController::class, 'index'])
            ->middleware('permission:ecommerce.cms.view|ecommerce.settings.view|module.ecommerce')
            ->name('cms.pages.index');
        Route::get('/cms/pages/create', [CmsPageController::class, 'create'])
            ->middleware('permission:ecommerce.cms.view|ecommerce.cms.manage|ecommerce.settings.view|module.ecommerce')
            ->name('cms.pages.create');
        Route::post('/cms/pages', [CmsPageController::class, 'store'])
            ->middleware('permission:ecommerce.cms.manage|ecommerce.settings.view|module.ecommerce')
            ->name('cms.pages.store');
        Route::post('/cms/pages/create-defaults', [CmsPageController::class, 'createDefaults'])
            ->middleware('permission:ecommerce.cms.manage|ecommerce.settings.view|module.ecommerce')
            ->name('cms.pages.create-defaults');
        Route::get('/cms/pages/{id}/edit', [CmsPageController::class, 'edit'])
            ->middleware('permission:ecommerce.cms.view|ecommerce.settings.view|module.ecommerce')
            ->name('cms.pages.edit');
        Route::put('/cms/pages/{id}', [CmsPageController::class, 'update'])
            ->middleware('permission:ecommerce.cms.manage|ecommerce.settings.view|module.ecommerce')
            ->name('cms.pages.update');
        Route::delete('/cms/pages/{id}', [CmsPageController::class, 'destroy'])
            ->middleware('permission:ecommerce.cms.manage|ecommerce.settings.view|module.ecommerce')
            ->name('cms.pages.destroy');

        // CMS - Bannières
        Route::get('/cms/banners', [CmsBannerController::class, 'index'])
            ->middleware('permission:ecommerce.cms.view|ecommerce.settings.view|module.ecommerce')
            ->name('cms.banners.index');
        Route::get('/cms/banners/create', [CmsBannerController::class, 'create'])
            ->middleware('permission:ecommerce.cms.view|ecommerce.cms.manage|ecommerce.settings.view|module.ecommerce')
            ->name('cms.banners.create');
        Route::get('/cms/banners/{id}/edit', [CmsBannerController::class, 'edit'])
            ->middleware('permission:ecommerce.cms.view|ecommerce.settings.view|module.ecommerce')
            ->name('cms.banners.edit');
        Route::post('/cms/banners', [CmsBannerController::class, 'store'])
            ->middleware('permission:ecommerce.cms.manage|ecommerce.settings.view|module.ecommerce')
            ->name('cms.banners.store');
        Route::put('/cms/banners/{id}', [CmsBannerController::class, 'update'])
            ->middleware('permission:ecommerce.cms.manage|ecommerce.settings.view|module.ecommerce')
            ->name('cms.banners.update');
        Route::delete('/cms/banners/{id}', [CmsBannerController::class, 'destroy'])
            ->middleware('permission:ecommerce.cms.manage|ecommerce.settings.view|module.ecommerce')
            ->name('cms.banners.destroy');

        // CMS - Blog
        Route::get('/cms/blog', [CmsBlogController::class, 'index'])
            ->middleware('permission:ecommerce.cms.view|ecommerce.settings.view|module.ecommerce')
            ->name('cms.blog.index');
        Route::get('/cms/blog/create', [CmsBlogController::class, 'create'])
            ->middleware('permission:ecommerce.cms.view|ecommerce.cms.manage|ecommerce.settings.view|module.ecommerce')
            ->name('cms.blog.create');
        Route::post('/cms/blog', [CmsBlogController::class, 'store'])
            ->middleware('permission:ecommerce.cms.manage|ecommerce.settings.view|module.ecommerce')
            ->name('cms.blog.store');
        Route::get('/cms/blog/{id}/edit', [CmsBlogController::class, 'edit'])
            ->middleware('permission:ecommerce.cms.view|ecommerce.settings.view|module.ecommerce')
            ->name('cms.blog.edit');
        Route::put('/cms/blog/{id}', [CmsBlogController::class, 'update'])
            ->middleware('permission:ecommerce.cms.manage|ecommerce.settings.view|module.ecommerce')
            ->name('cms.blog.update');
        Route::delete('/cms/blog/{id}', [CmsBlogController::class, 'destroy'])
            ->middleware('permission:ecommerce.cms.manage|ecommerce.settings.view|module.ecommerce')
            ->name('cms.blog.destroy');

        // CMS - Blog Catégories
        Route::get('/cms/blog/categories', [CmsBlogCategoryController::class, 'index'])
            ->middleware('permission:ecommerce.cms.view|ecommerce.settings.view|module.ecommerce')
            ->name('cms.blog.categories.index');
        Route::post('/cms/blog/categories', [CmsBlogCategoryController::class, 'store'])
            ->middleware('permission:ecommerce.cms.manage|ecommerce.settings.view|module.ecommerce')
            ->name('cms.blog.categories.store');
        Route::put('/cms/blog/categories/{id}', [CmsBlogCategoryController::class, 'update'])
            ->middleware('permission:ecommerce.cms.manage|ecommerce.settings.view|module.ecommerce')
            ->name('cms.blog.categories.update');
        Route::delete('/cms/blog/categories/{id}', [CmsBlogCategoryController::class, 'destroy'])
            ->middleware('permission:ecommerce.cms.manage|ecommerce.settings.view|module.ecommerce')
            ->name('cms.blog.categories.destroy');

        // CMS - Médias
        Route::get('/cms/media', [CmsMediaController::class, 'index'])
            ->middleware('permission:ecommerce.cms.view|ecommerce.settings.view|module.ecommerce')
            ->name('cms.media.index');
        Route::post('/cms/media', [CmsMediaController::class, 'store'])
            ->middleware('permission:ecommerce.cms.manage|ecommerce.settings.view|module.ecommerce')
            ->name('cms.media.store');
        Route::delete('/cms/media/{id}', [CmsMediaController::class, 'destroy'])
            ->middleware('permission:ecommerce.cms.manage|ecommerce.settings.view|module.ecommerce')
            ->name('cms.media.destroy');
    });

/*
 * Vitrine publique par sous-domaine (ex: monshop.omnisolution.shop)
 * Sans authentification ; la boutique est résolue via le middleware.
 */
$ecommerceBaseDomain = config('services.ecommerce.base_domain', 'omnisolution.shop');
Route::domain('{subdomain}.'.$ecommerceBaseDomain)
    ->middleware(['resolve.storefront.by.subdomain'])
    ->group(function () {
        Route::post('/_storefront/v', StorefrontVisitController::class)
            ->middleware('throttle:120,1')
            ->name('public.storefront.visit');
        Route::get('/', [StorefrontController::class, 'index'])->name('public.storefront.index');
        Route::get('/page/{slug}', [StorefrontController::class, 'showPage'])->name('public.storefront.page');
        Route::get('/blog', [StorefrontController::class, 'blog'])->name('public.storefront.blog');
        Route::get('/blog/{slug}', [StorefrontController::class, 'blogShow'])->name('public.storefront.blog.show');
        Route::get('/catalog', [StorefrontController::class, 'catalog'])->name('public.storefront.catalog');
        Route::get('/product/{id}', [StorefrontController::class, 'showProduct'])->name('public.storefront.product');
        Route::get('/cart', [StorefrontController::class, 'cart'])->name('public.storefront.cart');
    });
