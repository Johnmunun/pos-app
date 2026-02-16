<?php

namespace Src\Infrastructure\Admin\Repositories;

use Src\Domains\Admin\Repositories\AdminRepositoryInterface;
use Domains\User\Entities\User;
use Domains\Tenant\Entities\Tenant;
use App\Models\User as UserModel;
use App\Models\Tenant as TenantModel;

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
            return [
                'id' => $tenantModel->id,
                'name' => $tenantModel->name,
                'code' => $tenantModel->code,
                'email' => $tenantModel->email,
                'phone' => $tenantModel->phone,
                'address' => $tenantModel->address,
                'city' => $tenantModel->city,
                'country' => $tenantModel->country,
                'status' => $tenantModel->status,
                'logo' => $tenantModel->logo,
                'sector' => $tenantModel->sector,
                'slug' => $tenantModel->slug,
                'business_type' => $tenantModel->business_type,
                'legal_form' => $tenantModel->legal_form,
                'registration_number' => $tenantModel->registration_number,
                'tax_id' => $tenantModel->tax_id,
                'currency_code' => $tenantModel->currency_code,
                'timezone' => $tenantModel->timezone,
                'locale' => $tenantModel->locale,
                'users_count' => $tenantModel->users_count ?? 0,
                'shops_count' => $tenantModel->shops_count ?? 0,
            ];
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

        $isActive = $tenantModel->status ?? true;
        $createdAt = $tenantModel->created_at ? \DateTime::createFromInterface($tenantModel->created_at) : new \DateTime();
        $updatedAt = $tenantModel->updated_at ? \DateTime::createFromInterface($tenantModel->updated_at) : null;

        return Tenant::hydrate(
            $tenantModel->id,
            $tenantModel->code ?? '',
            $tenantModel->name ?? '',
            $tenantModel->email ?? '',
            $isActive,
            $createdAt,
            $updatedAt
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
        $tenantModel = TenantModel::withCount(['users', 'shops'])->find($id);
        if (!$tenantModel) {
            return [];
        }

        $isActive = $tenantModel->status ?? true;
        $createdAt = $tenantModel->created_at ? \DateTime::createFromInterface($tenantModel->created_at) : new \DateTime();
        $updatedAt = $tenantModel->updated_at ? \DateTime::createFromInterface($tenantModel->updated_at) : null;

        $tenantEntity = Tenant::hydrate(
            $tenantModel->id,
            $tenantModel->code ?? '',
            $tenantModel->name ?? '',
            $tenantModel->email ?? '',
            $isActive,
            $createdAt,
            $updatedAt
        );

        return [
            'tenant' => $tenantEntity,
            'stats' => [
                'users_count' => $tenantModel->users_count ?? 0,
                'shops_count' => $tenantModel->shops_count ?? 0,
            ],
        ];
    }
}