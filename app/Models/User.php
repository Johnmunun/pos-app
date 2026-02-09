<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'type',
        'slug',
        'tenant_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the tenant that owns the user.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the roles for the user.
     * Inclut les rôles globaux (tenant_id = null) et les rôles spécifiques au tenant.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    /**
     * Get the permissions for the user through roles.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission')
            ->withPivot('role_id');
    }

    /**
     * Vérifier si l'utilisateur est ROOT
     * Vérification robuste : trim + uppercase pour éviter les problèmes de casse/espaces
     *
     * @return bool
     */
    public function isRoot(): bool
    {
        $userType = strtoupper(trim($this->type ?? ''));
        return $userType === 'ROOT';
    }

    /**
     * Get the permission codes for the user.
     * Récupère les permissions de tous les rôles assignés (globaux et tenant-specific).
     */
    public function permissionCodes()
    {
        // Root users have all permissions (géré dans hasPermission)
        if ($this->isRoot()) {
            return ['*']; // Toutes les permissions
        }

        // Recharger les rôles et leurs permissions pour éviter le cache
        // Utiliser fresh() pour forcer le rechargement depuis la DB
        $roles = $this->roles()->with('permissions')->get();
        
        \Log::debug('User roles loaded', [
            'user_id' => $this->id,
            'user_email' => $this->email,
            'roles_count' => $roles->count(),
            'roles' => $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'tenant_id' => $role->pivot->tenant_id ?? null,
                    'permissions_count' => $role->permissions->count(),
                ];
            })->toArray(),
        ]);
        
        if ($roles->isEmpty()) {
            \Log::debug('User has no roles', [
                'user_id' => $this->id,
                'user_email' => $this->email,
                'user_type' => $this->type,
            ]);
            return [];
        }
        
        $permissions = $roles->flatMap(function ($role) {
            $rolePermissions = $role->permissions->pluck('code')->toArray();
            \Log::debug('Role permissions', [
                'user_id' => $this->id,
                'role_id' => $role->id,
                'role_name' => $role->name,
                'role_tenant_id' => $role->pivot->tenant_id ?? null,
                'permissions_count' => count($rolePermissions),
                'permissions' => $rolePermissions,
            ]);
            return $rolePermissions;
        })->unique()->values()->toArray();
        
        \Log::debug('User final permissions', [
            'user_id' => $this->id,
            'user_email' => $this->email,
            'total_permissions' => count($permissions),
            'permissions' => $permissions,
        ]);
        
        return $permissions;
    }

    /**
     * Check if user has a specific permission.
     * Root users have all permissions.
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        // Root users have all permissions
        if ($this->isRoot()) {
            return true;
        }
        
        // Check if user has the specific permission
        $permissions = $this->permissionCodes();
        
        // Si l'utilisateur a '*' (toutes les permissions), retourner true
        if (in_array('*', $permissions)) {
            return true;
        }
        
        return in_array($permission, $permissions);
    }
}