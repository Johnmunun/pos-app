<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Contexte module pour la gestion des vendeurs (Commerce, Quincaillerie).
 */
final class SellerModuleContext
{
    public function __construct(
        public readonly string $module,
        public readonly string $routePrefix,
        public readonly string $permissionPrefix,
        public readonly string $salesIndexRoute,
        public readonly string $inertiaPage,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $module = (string) ($request->route('sellerModule') ?? 'commerce');

        return match ($module) {
            'hardware' => new self(
                module: 'hardware',
                routePrefix: 'hardware.sellers',
                permissionPrefix: 'hardware.seller',
                salesIndexRoute: 'hardware.sales.index',
                inertiaPage: 'Commerce/Sellers/Index',
            ),
            default => new self(
                module: 'commerce',
                routePrefix: 'commerce.sellers',
                permissionPrefix: 'commerce.seller',
                salesIndexRoute: 'commerce.sales.index',
                inertiaPage: 'Commerce/Sellers/Index',
            ),
        };
    }

    public function permission(string $action): string
    {
        return $this->permissionPrefix.'.'.$action;
    }

    public function route(string $action, mixed $parameters = []): string
    {
        return route($this->routePrefix.'.'.$action, $parameters);
    }
}
