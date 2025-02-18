<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function it_can_assign_roles_to_users()
    {
        $user = User::factory()->create();
        $adminRole = Role::findByName('admin');
        $managerRole = Role::findByName('manager');
        $salesRepRole = Role::findByName('sales_rep');

        $user->assignRole($adminRole);
        $this->assertTrue($user->hasRole('admin'));

        $user->removeRole($adminRole);
        $user->assignRole($managerRole);
        $this->assertTrue($user->hasRole('manager'));

        $user->removeRole($managerRole);
        $user->assignRole($salesRepRole);
        $this->assertTrue($user->hasRole('sales_rep'));
    }

    #[Test]
    public function it_can_assign_permissions_to_roles()
    {
        $managerRole = Role::findByName('manager');
        $viewClientPermission = Permission::findByName('view_client');
        $createClientPermission = Permission::findByName('create_client');

        $managerRole->givePermissionTo($viewClientPermission, $createClientPermission);

        $this->assertTrue($managerRole->hasPermissionTo('view_client'));
        $this->assertTrue($managerRole->hasPermissionTo('create_client'));
    }

    #[Test]
    public function it_restricts_access_based_on_user_role()
    {
        $admin = User::factory()->create()->assignRole('admin');
        $manager = User::factory()->create()->assignRole('manager');
        $salesRep = User::factory()->create()->assignRole('sales_rep');

        $this->actingAs($admin);
        $this->get('/admin/dashboard')->assertStatus(200);

        $this->actingAs($manager);
        $this->get('/admin/dashboard')->assertStatus(403);

        $this->actingAs($salesRep);
        $this->get('/admin/dashboard')->assertStatus(403);
    }

    #[Test]
    public function it_allows_access_based_on_user_permissions()
    {
        $manager = User::factory()->create()->assignRole('manager');
        $salesRep = User::factory()->create()->assignRole('sales_rep');

        $this->actingAs($manager);
        $this->get('/clients/create')->assertStatus(200);

        $this->actingAs($salesRep);
        $this->get('/clients/create')->assertStatus(403);
    }
}
