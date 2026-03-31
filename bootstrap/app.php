<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'api/billing/payments/callback',
            // Ping audience vitrine (sous-domaine public, POST sans session CSRF fiable)
            '_storefront/v',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\EnsureUserIsActive::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            // Log des actions utilisateurs (audit)
            \App\Http\Middleware\LogUserActivity::class,
        ]);

        $middleware->alias([
            'root' => \App\Http\Middleware\CheckRootUser::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'ensure.user.is.active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'resolve.storefront.by.subdomain' => \App\Http\Middleware\ResolveStorefrontShopBySubdomain::class,
            'feature.enabled' => \App\Http\Middleware\EnsureFeatureEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // CSRF expiré + Inertia : redirection pleine page (évite le HTML « Page Expired » dans une modale).
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 419 && $request->header('X-Inertia')) {
                return response('', 409)
                    ->header('X-Inertia-Location', $request->fullUrl());
            }
        });

        // Handle 403 errors with Inertia
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 403) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $e->getMessage() ?: 'Permission denied.',
                    ], 403);
                }

                // Render Inertia page for 403 errors
                return \Inertia\Inertia::render('Errors/403', [
                    'status' => 403,
                    'message' => $e->getMessage() ?: 'Permission denied.',
                ])->toResponse($request)->setStatusCode(403);
            }
        });
    })->create();
