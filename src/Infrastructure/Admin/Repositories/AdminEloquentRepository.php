<?php

namespace Src\Infrastructure\Admin\Repositories;

use Src\Domains\Admin\Repositories\AdminRepositoryInterface;
use Domains\User\Entities\User;
use Src\Domains\Tenant\Entities\Tenant;
use App\Models\User as UserModel;
use App\Models\Tenant as TenantModel;
use Src\Domains\User\ValueObjects\UserId;
use Src\Domains\Tenant\ValueObjects\TenantId;

class AdminEloquentRepository implements AdminRepositoryInterface
{
    public function getAllUsers(): array
    {
        $users = UserModel::with(['tenant', 'roles'])->get();
        return $users->map(function ($userModel) {
            // Gérer first_name/last_name ou name
            $firstName = $userModel->first_name;
            $lastName = $userModel->last_name;
            
            // Si first_name/last_name n'existent pas, utiliser name
            if (!$firstName && !$lastName && $userModel->name) {
                $nameParts = explode(' ', $userModel->name, 2);
                $firstName = $nameParts[0] ?? '';
                $lastName = $nameParts[1] ?? '';
            }
            
            // Convertir en tableau pour Inertia (sérialisation)
            return [
                'id' => $userModel->id,
                'email' => $userModel->email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => $userModel->name ?? ($firstName . ' ' . $lastName),
                'type' => $userModel->type ?? 'MERCHANT',
                'status' => $userModel->status ?? 'pending',
                'tenant_id' => $userModel->tenant_id,
                'is_active' => $userModel->is_active ?? true,
                'last_login_at' => $userModel->last_login_at ? $userModel->last_login_at->toDateTimeString() : null,
                'created_at' => $userModel->created_at ? $userModel->created_at->toDateTimeString() : null,
                'updated_at' => $userModel->updated_at ? $userModel->updated_at->toDateTimeString() : null,
                'tenant' => $userModel->tenant ? [
                    'id' => $userModel->tenant->id,
                    'name' => $userModel->tenant->name,
                    'code' => $userModel->tenant->code,
                ] : null,
                'roles' => $userModel->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }
    
    public function getAllTenants(): array
    {
        $tenants = TenantModel::withCount(['users', 'shops'])->get();
        return $tenants->map(function ($tenantModel) {
            return Tenant::fromPrimitives(
                $tenantModel->id,
                $tenantModel->name,
                $tenantModel->code,
                $tenantModel->email,
                $tenantModel->phone,
                $tenantModel->address,
                $tenantModel->city,
                $tenantModel->country,
                $tenantModel->status,
                $tenantModel->logo,
                $tenantModel->sector,
                $tenantModel->slug,
                $tenantModel->business_type,
                $tenantModel->legal_form,
                $tenantModel->registration_number,
                $tenantModel->tax_id,
                $tenantModel->currency_code,
                $tenantModel->timezone,
                $tenantModel->locale
            );
        })->toArray();
    }
    
    public function getUserById(int $id): ?User
    {
        $userModel = UserModel::find($id);
        if (!$userModel) {
            return null;
        }
        
        return User::hydrate(
            id: $userModel->id,
            email: $userModel->email,
            passwordHash: $userModel->password,
            firstName: $userModel->first_name,
            lastName: $userModel->last_name,
            type: $userModel->type,
            tenantId: $userModel->tenant_id,
            isActive: $userModel->is_active ?? true,
            lastLoginAt: $userModel->last_login_at ? new \DateTime($userModel->last_login_at) : null,
            createdAt: $userModel->created_at ? new \DateTime($userModel->created_at) : new \DateTime(),
            updatedAt: $userModel->updated_at ? new \DateTime($userModel->updated_at) : null
        );
    }
    
    public function getTenantById(int $id): ?Tenant
    {
        $tenantModel = TenantModel::find($id);
        if (!$tenantModel) {
            return null;
        }
        
        return Tenant::fromPrimitives(
            $tenantModel->id,
            $tenantModel->name,
            $tenantModel->code,
            $tenantModel->email,
            $tenantModel->phone,
            $tenantModel->address,
            $tenantModel->city,
            $tenantModel->country,
            $tenantModel->status,
            $tenantModel->logo,
            $tenantModel->sector,
            $tenantModel->slug,
            $tenantModel->business_type,
            $tenantModel->legal_form,
            $tenantModel->registration_number,
            $tenantModel->tax_id,
            $tenantModel->currency_code,
            $tenantModel->timezone,
            $tenantModel->locale
        );
    }
    
    public function updateUserStatus(int $id, bool $status): void
    {
        $userModel = UserModel::find($id);
        if ($userModel) {
            $userModel->update(['status' => $status]);
        }
    }
    
    public function updateTenantStatus(int $id, bool $status): void
    {
        $tenantModel = TenantModel::find($id);
        if ($tenantModel) {
            $tenantModel->update(['status' => $status]);
        }
    }
    
    public function getTenantWithStats(int $id): array
    {
        $tenant = TenantModel::withCount(['users', 'shops'])->find($id);
        if (!$tenant) {
            return [];
        }
        
        $tenantEntity = Tenant::fromPrimitives(
            $tenant->id,
            $tenant->name,
            $tenant->code,
            $tenant->email,
            $tenant->phone,
            $tenant->address,
            $tenant->city,
            $tenant->country,
            $tenant->status,
            $tenant->logo,
            $tenant->sector,
            $tenant->slug,
            $tenant->business_type,
            $tenant->legal_form,
            $tenant->registration_number,
            $tenant->tax_id,
            $tenant->currency_code,
            $tenant->timezone,
            $tenant->locale
        );
        
        return [
            'tenant' => $tenantEntity,
            'stats' => [
                'users_count' => $tenant->users_count,
                'shops_count' => $tenant->shops_count,
            ]
        ];
    }
}