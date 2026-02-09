<?php

namespace Src\Domains\Admin\UseCases;

use Src\Domains\Admin\Repositories\AdminRepositoryInterface;

class GetAllTenantsUseCase
{
    private AdminRepositoryInterface $repository;

    public function __construct(AdminRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function execute(): array
    {
        return $this->repository->getAllTenants();
    }
}