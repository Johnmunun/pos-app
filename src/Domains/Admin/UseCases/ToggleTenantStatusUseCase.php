<?php

namespace Src\Domains\Admin\UseCases;

use Src\Domains\Admin\Repositories\AdminRepositoryInterface;

class ToggleTenantStatusUseCase
{
    private AdminRepositoryInterface $repository;

    public function __construct(AdminRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function execute(int $tenantId): void
    {
        $tenant = $this->repository->getTenantById($tenantId);
        if (!$tenant) {
            throw new \Exception("Tenant not found");
        }
        
        $newStatus = !$tenant->isActive();
        $this->repository->updateTenantStatus($tenantId, $newStatus);
    }
}