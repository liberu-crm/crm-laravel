<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Models\Team;
use App\Models\User;
use App\Services\TeamManagementService;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeamMemberCustomRoleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Team $team;

    private User $member;

    private Role $customRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);

        $this->admin = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $this->team = $this->admin->currentTeam;
        setPermissionsTeamId($this->team->id);
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($this->team);

        // Member currently on a fixed role.
        $this->member = User::factory()->create();
        $this->team->users()->attach($this->member);
        setPermissionsTeamId($this->team->id);
        $this->member->assignRole('free');

        // Team-scoped custom role created via TeamRoleResource.
        $this->customRole = Role::create(['name' => 'Support', 'guard_name' => 'web', 'team_id' => $this->team->id]);
    }

    public function test_assign_custom_role_replaces_the_members_fixed_role(): void
    {
        app(TeamManagementService::class)->assignCustomRole($this->member, $this->team, $this->customRole);

        setPermissionsTeamId($this->team->id);
        $fresh = $this->member->fresh();
        $this->assertTrue($fresh->hasRole('Support'));
        $this->assertFalse($fresh->hasRole('free'));
    }

    public function test_custom_role_from_another_team_is_not_assignable(): void
    {
        $otherTeam = Team::factory()->create();
        $foreignRole = Role::create(['name' => 'Intruder', 'guard_name' => 'web', 'team_id' => $otherTeam->id]);

        $this->expectException(InvalidArgumentException::class);

        app(TeamManagementService::class)->assignCustomRole($this->member, $this->team, $foreignRole);
    }

    public function test_assigning_a_custom_role_writes_an_audit_entry(): void
    {
        app(TeamManagementService::class)->assignCustomRole($this->member, $this->team, $this->customRole);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'team.role_changed',
            'auditable_type' => User::class,
            'auditable_id' => $this->member->id,
            'user_id' => $this->admin->id,
        ]);
    }
}
