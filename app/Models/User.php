<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

/**
 * Model Eloquent: User
 *
 * Représentation de la table 'users'.
 * Utilisateurs de l'application POS SaaS.
 *
 * Types d'utilisateurs:
 * - ROOT: Propriétaire de l'application
 * - TENANT_ADMIN: Admin d'un tenant
 * - MERCHANT, SELLER, STAFF: Utilisateurs standards
 */
class User extends Authenticatable
{
    use HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'first_name',
        'last_name',
        'type',
        'tenant_id',
        'is_active',
        'last_login_at',
        'email_verified_at',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = ['password'];

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->where('type', 'ROOT');
    }

    /**
     * Relations
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    public function permissions()
    {
        return $this->hasManyThrough(Permission::class, Role::class);
    }

    /**
     * Vérifier si l'utilisateur possède une permission.
     * 
     * Le ROOT user a accès à toutes les permissions par défaut.
     */
    public function hasPermission(string $code): bool
    {
        // Le ROOT user a accès à toutes les permissions
        if ($this->type === 'ROOT') {
            return true;
        }

        return DB::table('permissions')
            ->join('role_permission', 'permissions.id', '=', 'role_permission.permission_id')
            ->join('roles', 'roles.id', '=', 'role_permission.role_id')
            ->join('user_role', 'roles.id', '=', 'user_role.role_id')
            ->where('user_role.user_id', $this->id)
            ->where('permissions.code', $code)
            ->where('permissions.is_old', false)
            ->where('roles.is_active', true)
            ->exists();
    }

    /**
     * Récupérer la liste des permissions de l'utilisateur.
     *
     * @return array<int, string>
     */
    public function permissionCodes(): array
    {
        return DB::table('permissions')
            ->join('role_permission', 'permissions.id', '=', 'role_permission.permission_id')
            ->join('roles', 'roles.id', '=', 'role_permission.role_id')
            ->join('user_role', 'roles.id', '=', 'user_role.role_id')
            ->where('user_role.user_id', $this->id)
            ->where('permissions.is_old', false)
            ->where('roles.is_active', true)
            ->distinct()
            ->orderBy('permissions.code')
            ->pluck('permissions.code')
            ->all();
    }
}
