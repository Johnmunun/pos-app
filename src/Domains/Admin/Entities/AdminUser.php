<?php

namespace Src\Domains\Admin\Entities;

use Domains\User\Entities\User;

class AdminUser extends User
{
    /**
     * Permissions (pour compatibilité avec vérifications admin).
     * @var array<int, object{name?: string}>
     */
    protected array $permissions = [];

    public function canAccessAdminPanel(): bool
    {
        return $this->isRoot() || $this->hasAdminPermissions();
    }

    private function hasAdminPermissions(): bool
    {
        $names = array_map(fn (object $p) => $p->name ?? null, $this->permissions);
        return in_array('admin.access', $names, true) ||
               in_array('admin.users.view', $names, true) ||
               in_array('admin.tenants.view', $names, true);
    }
}