<?php

namespace Src\Domains\Admin\Entities;

use Domains\User\Entities\User;

class AdminUser extends User
{
    public function canAccessAdminPanel(): bool
    {
        return $this->type === 'ROOT' || $this->hasAdminPermissions();
    }

    private function hasAdminPermissions(): bool
    {
        // Check if user has admin-related permissions
        return $this->permissions->contains('name', 'admin.access') ||
               $this->permissions->contains('name', 'admin.users.view') ||
               $this->permissions->contains('name', 'admin.tenants.view');
    }
}