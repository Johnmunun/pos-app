<?php

namespace Src\Infrastructure\StoreProvisioning;

use App\Models\Tenant;
use App\Models\User;
use Src\Domains\StoreProvisioning\Contracts\StoreTemplateProvisionerInterface;
use Src\Domains\StoreProvisioning\StoreStartMode;

final class StoreTemplateProvisioner implements StoreTemplateProvisionerInterface
{
    public function __construct(
        private readonly TenantPhysicalStoreBootstrap $bootstrap,
        private readonly DefaultCurrencyProvisioner $currencies,
        private readonly SectorExcelTemplateImporter $excelImporter,
    ) {}

    public function provisionTenantStore(Tenant $tenant, User $adminUser): void
    {
        /** @var Tenant $locked */
        $locked = Tenant::query()->lockForUpdate()->findOrFail($tenant->id);
        if ($locked->is_store_initialized) {
            return;
        }

        $mode = $locked->store_start_mode ?? StoreStartMode::EMPTY_STORE;
        $sector = (string) ($locked->sector ?? 'other');

        $boot = $this->bootstrap->ensureDepotShopAndSettings($locked, $adminUser);
        $shop = $boot['shop'];
        $depot = $boot['depot'];

        $this->currencies->ensureForTenant((int) $locked->id, $shop);

        if ($mode === StoreStartMode::PRECONFIGURED_STORE) {
            $this->excelImporter->import((int) $locked->id, $shop, $depot->id, $sector);
        }

        $templateCode = $mode === StoreStartMode::PRECONFIGURED_STORE
            ? $sector.'_preconfigured_'.config('store_templates.template_version', 'v1')
            : 'empty_'.$sector.'_'.config('store_templates.template_version', 'v1');

        $locked->forceFill([
            'is_store_initialized' => true,
            'template_code' => $templateCode,
            'template_applied_at' => now(),
        ])->save();
    }
}
