<?php

namespace Src\Domains\Admin\Repositories;

use Domains\User\Entities\User;
use Src\Domains\Tenant\Entities\Tenant;

interface AdminRepositoryInterface
{
    public function getAllUsers(): array;
    
    public function getAllTenants(): array;
    
    public function getUserById(int $id): ?User;
    
    public function getTenantById(int $id): ?Tenant;
    
    public function updateUserStatus(int $id, bool $status): void;
    
    public function updateTenantStatus(int $id, bool $status): void;
    
    public function getTenantWithStats(int $id): array;
}