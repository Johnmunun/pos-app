<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_sellers_page(): void
    {
        $response = $this->get('/pharmacy/sellers');
        $response->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_access_sellers_page(): void
    {
        $tenant = Tenant::factory()->create(['sector' => 'pharmacy']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        
        $response = $this->actingAs($user)->get('/pharmacy/sellers');
        $response->assertStatus(403);
    }

    public function test_user_with_permission_can_access_sellers_page(): void
    {
        $tenant = Tenant::factory()->create(['sector' => 'pharmacy']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        
        // Créer une permission pharmacy.seller.view
        $permission = Permission::factory()->create([
            'code' => 'pharmacy.seller.view',
            'is_old' => false,
        ]);
        
        // Assigner la permission à l'utilisateur via un rôle
        $role = Role::factory()->create(['tenant_id' => $tenant->id]);
        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        
        $response = $this->actingAs($user)->get('/pharmacy/sellers');
        $response->assertStatus(200);
    }
}
