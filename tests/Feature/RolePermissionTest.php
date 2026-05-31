<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    #[Test]
    public function it_creates_required_roles(): void
    {
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'manager']);
        $this->assertDatabaseHas('roles', ['name' => 'sales_rep']);
    }

    #[Test]
    public function it_can_assign_roles_to_users(): void
    {
        $user = User::factory()->create();
        $adminRole = Role::findByName('admin');

        $user->assignRole($adminRole);
        $this->assertTrue($user->hasRole('admin'));

        $user->removeRole($adminRole);
        $this->assertFalse($user->hasRole('admin'));
    }

    #[Test]
    public function it_can_assign_manager_role(): void
    {
        $user = User::factory()->create();
        $managerRole = Role::findByName('manager');

        $user->assignRole($managerRole);
        $this->assertTrue($user->hasRole('manager'));
    }

    #[Test]
    public function it_can_create_custom_permissions(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'test_custom_permission']);

        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        $this->assertTrue($user->hasPermissionTo('test_custom_permission'));
    }

    #[Test]
    public function admin_user_can_be_created_with_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('manager'));
    }
}
