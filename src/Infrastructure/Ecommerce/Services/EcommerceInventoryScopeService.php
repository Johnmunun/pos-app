<?php

declare(strict_types=1);

namespace Src\Infrastructure\Ecommerce\Services;

use App\Models\Shop;
use App\Services\TenantBackofficeShopResolver;
use Illuminate\Http\Request;

/**
 * Délègue à {@see TenantBackofficeShopResolver} pour un seul endroit de vérité
 * (boutique canonique + shop_id gc_* legacy).
 */
final class EcommerceInventoryScopeService
{
    public function __construct(
        private readonly TenantBackofficeShopResolver $resolver
    ) {
    }

    public function resolveShop(Request $request): Shop
    {
        return $this->resolver->resolveShop($request);
    }

    /**
     * @return list<string>
     */
    public function gcInventoryShopIds(Shop $shop, ?string $tenantId): array
    {
        return $this->resolver->globalCommerceInventoryShopIds($shop, $tenantId);
    }
}
