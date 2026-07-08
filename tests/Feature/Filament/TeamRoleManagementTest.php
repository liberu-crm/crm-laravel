<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\Role;
use App\Filament\App\Resources\TeamMemberResource;
use App\Filament\App\Resources\TeamMemberResource\Pages\ListTeamMembers;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamManagementService;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class TeamRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private $team;

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
    }

    private function member(): User
    {
        $member = User::factory()->create(['email_verified_at' => now()]);
        $this->team->users()->attach($member);

        return $member;
    }

    public function test_admin_sees_only_their_own_teams_members(): void
    {
        $mine = $this->member();
        $otherTeam = Team::factory()->create();
        $stranger = User::factory()->create();
        $otherTeam->users()->attach($stranger);

        Livewire::test(ListTeamMembers::class)
            ->assertCanSeeTableRecords([$mine, $this->admin])
            ->assertCanNotSeeTableRecords([$stranger]);
    }

    public function test_changing_a_role_writes_the_team_scoped_role(): void
    {
        $member = $this->member();

        Livewire::test(ListTeamMembers::class)
            ->callTableAction('changeRole', $member, data: ['role' => Role::Manager->value]);

        setPermissionsTeamId($this->team->id);
        $this->assertTrue($member->fresh()->hasRole(Role::Manager->value));

        // Not leaked to a different team's context.
        $otherTeam = Team::factory()->create();
        setPermissionsTeamId($otherTeam->id);
        $this->assertFalse($member->fresh()->hasRole(Role::Manager->value));
    }

    public function test_change_team_role_rejects_a_non_team_role(): void
    {
        $member = $this->member();

        $this->expectException(InvalidArgumentException::class);
        app(TeamManagementService::class)->changeTeamRole($member, $this->team, Role::SuperAdmin);
    }

    public function test_only_admins_can_access(): void
    {
        $this->assertTrue(TeamMemberResource::canAccess());

        $manager = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        setPermissionsTeamId($manager->currentTeam->id);
        $manager->assignRole('manager');
        $this->actingAs($manager);

        $this->assertFalse(TeamMemberResource::canAccess());
    }

    public function test_change_role_action_is_hidden_on_own_row(): void
    {
        $member = $this->member();

        Livewire::test(ListTeamMembers::class)
            ->assertTableActionHidden('changeRole', $this->admin)
            ->assertTableActionVisible('changeRole', $member);
    }
}
