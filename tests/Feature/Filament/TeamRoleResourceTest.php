<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\TeamRoleResource;
use App\Filament\App\Resources\TeamRoleResource\Pages\CreateTeamRole;
use App\Filament\App\Resources\TeamRoleResource\Pages\ListTeamRoles;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class TeamRoleResourceTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private function actAsAdmin(): User
    {
        $this->seed(RolesSeeder::class);
        Permission::firstOrCreate(['name' => 'view_contact', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_roles', 'guard_name' => 'web']);

        $admin = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $this->team = $admin->currentTeam;
        setPermissionsTeamId($this->team->id);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($this->team);

        return $admin;
    }

    public function test_admin_creates_a_team_scoped_custom_role_with_permission(): void
    {
        $this->actAsAdmin();

        Livewire::test(CreateTeamRole::class)
            ->fillForm(['name' => 'Sales Lead', 'permissions' => ['view_contact']])
            ->call('create')
            ->assertHasNoFormErrors();

        $role = SpatieRole::where('team_id', $this->team->id)->where('name', 'Sales Lead')->first();
        $this->assertNotNull($role);
        $this->assertSame('web', $role->guard_name);
        $this->assertTrue($role->hasPermissionTo('view_contact'));
    }

    public function test_list_shows_only_current_team_custom_roles(): void
    {
        $this->actAsAdmin();

        $mine = SpatieRole::create(['name' => 'Mine', 'guard_name' => 'web', 'team_id' => $this->team->id]);
        $otherTeam = SpatieRole::create(['name' => 'Theirs', 'guard_name' => 'web', 'team_id' => Team::factory()->create()->id]);
        $system = SpatieRole::where('name', 'manager')->whereNull('team_id')->first();

        Livewire::test(ListTeamRoles::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$otherTeam, $system]);
    }

    public function test_access_gated_to_admins(): void
    {
        $team = Team::factory()->create();
        $this->seed(RolesSeeder::class);

        $admin = User::factory()->create();
        setPermissionsTeamId($team->id);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $this->assertTrue(TeamRoleResource::canAccess());

        $rep = User::factory()->create();
        setPermissionsTeamId($team->id);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);
        $this->assertFalse(TeamRoleResource::canAccess());
    }

    public function test_management_permissions_are_not_grantable(): void
    {
        $this->actAsAdmin();

        // manage_roles is excluded from the grantable options...
        $this->assertArrayNotHasKey('manage_roles', TeamRoleResource::grantablePermissions());
        $this->assertArrayHasKey('view_contact', TeamRoleResource::grantablePermissions());

        // ...and a crafted submit including it is rejected (the options `in` rule
        // enforces the grantable set server-side), so the role is not created.
        Livewire::test(CreateTeamRole::class)
            ->fillForm(['name' => 'Sneaky', 'permissions' => ['view_contact', 'manage_roles']])
            ->call('create');

        $this->assertNull(SpatieRole::where('team_id', $this->team->id)->where('name', 'Sneaky')->first());
    }

    public function test_grantable_only_permission_is_accepted(): void
    {
        $this->actAsAdmin();

        Livewire::test(CreateTeamRole::class)
            ->fillForm(['name' => 'Support', 'permissions' => ['view_contact']])
            ->call('create')
            ->assertHasNoFormErrors();

        $role = SpatieRole::where('team_id', $this->team->id)->where('name', 'Support')->first();
        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermissionTo('view_contact'));
        $this->assertFalse($role->hasPermissionTo('manage_roles'));
    }
}
