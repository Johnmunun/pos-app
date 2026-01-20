<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PermissionSyncService;
use App\Services\RootRoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RootUserAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test qu'un utilisateur ROOT peut accéder aux pages admin
     */
    public function test_root_user_can_access_admin_pages()
    {
        // Créer un utilisateur ROOT
        $rootUser = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Root',
            'email' => 'root@test.local',
            'password' => bcrypt('password'),
            'type' => 'ROOT',
            'tenant_id' => null,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        app(PermissionSyncService::class)->syncFromDefaultFile();
        $roleService = app(RootRoleService::class);
        $rootRole = $roleService->ensureRootRole();
        $roleService->syncRolePermissions($rootRole);
        $roleService->assignRoleToUser($rootUser, $rootRole);

        // Se connecter
        $response = $this->actingAs($rootUser)
            ->get('/admin/select-tenant');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/SelectTenant')
        );
    }

    /**
     * Test qu'un utilisateur non-ROOT ne peut pas accéder aux pages admin
     */
    public function test_non_root_user_cannot_access_admin_pages()
    {
        // Créer un utilisateur normal
        $user = User::create([
            'first_name' => 'User',
            'last_name' => 'Normal',
            'email' => 'user@test.local',
            'password' => bcrypt('password'),
            'type' => 'MERCHANT',
            'tenant_id' => 1,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Essayer d'accéder
        $response = $this->actingAs($user)
            ->get('/admin/select-tenant');

        $response->assertStatus(302); // Redirect
        $response->assertRedirect();
    }

    /**
     * Test qu'un utilisateur non connecté est redirigé
     */
    public function test_unauthenticated_user_is_redirected_from_admin()
    {
        $response = $this->get('/admin/select-tenant');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     * Test la redirection après connexion pour ROOT
     */
    public function test_root_user_redirected_to_admin_after_login()
    {
        $rootUser = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Root',
            'email' => 'root@test.local',
            'password' => bcrypt('password'),
            'type' => 'ROOT',
            'tenant_id' => null,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        app(PermissionSyncService::class)->syncFromDefaultFile();
        $roleService = app(RootRoleService::class);
        $rootRole = $roleService->ensureRootRole();
        $roleService->syncRolePermissions($rootRole);
        $roleService->assignRoleToUser($rootUser, $rootRole);

        // Se connecter via formulaire
        $response = $this->post('/login', [
            'email' => 'root@test.local',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/select-tenant');
    }

    /**
     * Test la redirection après connexion pour utilisateur normal
     */
    public function test_normal_user_redirected_to_dashboard_after_login()
    {
        $user = User::create([
            'first_name' => 'User',
            'last_name' => 'Normal',
            'email' => 'user@test.local',
            'password' => bcrypt('password'),
            'type' => 'MERCHANT',
            'tenant_id' => 1,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Se connecter via formulaire
        $response = $this->post('/login', [
            'email' => 'user@test.local',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
    }
}
