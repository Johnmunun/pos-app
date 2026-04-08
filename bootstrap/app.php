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
            // Checkout vitrine publique (sous-domaine): requis pour clients non authentifiés
            'orders',
            'ecommerce/orders',
            'payments/fusionpay/initiate',
            'api/ecommerce/payments/fusionpay/initiate',
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
        $isMobileApi = static function (\Illuminate\Http\Request $request): bool {
            return $request->is('api/v1/mobile/*');
        };

        // Mobile API: normalize validation errors for React Native consumers.
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) use ($isMobileApi) {
            if (! $isMobileApi($request)) {
                return null;
            }

            $errors = $e->errors();
            $message = (string) (collect($errors)->flatten()->first() ?? 'Validation error.');

            return response()->json([
                'message' => $message,
                'code' => 'VALIDATION_ERROR',
                'errors' => $errors,
            ], $e->status);
        });

        // Mobile API: explicit unauthenticated response.
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) use ($isMobileApi) {
            if (! $isMobileApi($request)) {
                return null;
            }

            return response()->json([
                'message' => 'Authentication required. Please login first.',
                'code' => 'UNAUTHENTICATED',
            ], 401);
        });

        // Mobile API: explicit authorization response.
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, \Illuminate\Http\Request $request) use ($isMobileApi) {
            if (! $isMobileApi($request)) {
                return null;
            }

            return response()->json([
                'message' => 'You do not have permission to perform this action.',
                'code' => 'FORBIDDEN',
            ], 403);
        });

        // Mobile API: explicit 404 for routes/resources.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) use ($isMobileApi) {
            if (! $isMobileApi($request)) {
                return null;
            }

            return response()->json([
                'message' => 'Endpoint not found.',
                'code' => 'NOT_FOUND',
            ], 404);
        });

        // Mobile API: explicit 405 with allowed methods.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, \Illuminate\Http\Request $request) use ($isMobileApi) {
            if (! $isMobileApi($request)) {
                return null;
            }

            $allowHeader = $e->getHeaders()['Allow'] ?? '';
            $allowedMethods = is_string($allowHeader) && $allowHeader !== ''
                ? array_map('trim', explode(',', $allowHeader))
                : [];

            return response()->json([
                'message' => 'HTTP method not allowed for this endpoint.',
                'code' => 'METHOD_NOT_ALLOWED',
                'allowed_methods' => $allowedMethods,
            ], 405);
        });

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

        // Mobile API: final fallback to avoid generic HTML/500 pages.
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) use ($isMobileApi) {
            if (! $isMobileApi($request)) {
                return null;
            }

            \Illuminate\Support\Facades\Log::error('Unhandled mobile API exception', [
                'path' => $request->path(),
                'method' => $request->method(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Server temporarily unavailable. Please try again.',
                'code' => 'INTERNAL_SERVER_ERROR',
            ], 500);
        });
    })->create();
