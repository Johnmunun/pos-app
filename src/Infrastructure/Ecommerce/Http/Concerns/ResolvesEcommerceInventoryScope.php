<?php

declare(strict_types=1);

namespace Src\Infrastructure\Ecommerce\Http\Concerns;

use App\Models\Shop;
use Illuminate\Http\Request;
use Src\Infrastructure\Ecommerce\Services\EcommerceInventoryScopeService;

trait ResolvesEcommerceInventoryScope
{
    protected function ecommerceInventoryShop(Request $request): Shop
    {
        return app(EcommerceInventoryScopeService::class)->resolveShop($request);
    }

    /**
     * @return list<string>
     */
    protected function ecommerceGcShopIds(Request $request, Shop $shop): array
    {
        $user = $request->user();
        $tenantId = $user && $user->tenant_id !== null && $user->tenant_id !== '' ? (string) $user->tenant_id : null;

        return app(EcommerceInventoryScopeService::class)->gcInventoryShopIds($shop, $tenantId);
    }

    protected function ecommercePrimaryShopId(Request $request): string
    {
        return (string) $this->ecommerceInventoryShop($request)->id;
    }
}
