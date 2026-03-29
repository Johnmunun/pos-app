<?php

namespace Src\Domains\StoreProvisioning\Contracts;

use App\Models\Tenant;
use App\Models\User;

/**
 * Provisionnement boutique post-inscription (devises, templates Excel, idempotence).
 */
interface StoreTemplateProvisionerInterface
{
    /**
     * Crée dépôt + boutique si besoin, devises de base, et éventuellement le pack métier.
     * Idempotent : ne ré-applique pas si déjà initialisé.
     */
    public function provisionTenantStore(Tenant $tenant, User $adminUser): void;
}
