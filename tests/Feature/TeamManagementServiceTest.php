<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use App\Services\TeamManagementService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    private TeamManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TeamManagementService::class);
    }

    public function test_creates_personal_team_owned_by_user(): void
    {
        $user = User::factory()->create(['name' => 'Ada']);

        $team = $this->service->createPersonalTeamForUser($user);

        $this->assertTrue($team->personal_team);
        $this->assertSame("Ada's Team", $team->name);
        $this->assertTrue($user->ownsTeam($team));
        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'user_id' => $user->id,
            'personal_team' => true,
        ]);
    }

    public function test_assign_user_to_team_adds_membership_and_switches_current_team(): void
    {
        // Team owned by someone else — the realistic "join a shared team" case.
        $team = Team::factory()->create(['personal_team' => false]);
        $user = User::factory()->create();

        $this->service->assignUserToTeam($user, $team);

        $this->assertTrue($user->fresh()->belongsToTeam($team));
        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);
        $this->assertSame($team->id, $user->fresh()->current_team_id);
    }

    public function test_switch_team_throws_when_user_is_not_a_member(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User does not belong to the specified team.');

        $this->service->switchTeam($user, $team);
    }

    public function test_switch_team_switches_current_team_for_a_member(): void
    {
        $team = Team::factory()->create(['personal_team' => false]);
        $user = User::factory()->create();
        $user->teams()->attach($team, ['role' => 'member']);

        $this->service->switchTeam($user, $team);

        $this->assertSame($team->id, $user->fresh()->current_team_id);
    }

    public function test_assign_user_to_default_team_uses_existing_non_personal_team(): void
    {
        $defaultTeam = Team::factory()->create(['personal_team' => false]);
        $user = User::factory()->create();

        $this->service->assignUserToDefaultTeam($user);

        $this->assertTrue($user->fresh()->belongsToTeam($defaultTeam));
        $this->assertSame($defaultTeam->id, $user->fresh()->current_team_id);
    }

    public function test_assign_user_to_default_team_falls_back_to_personal_team_when_none_exists(): void
    {
        // Fresh DB: no non-personal team and no Branch model/table exist,
        // so the service must fall back to creating a personal team.
        $user = User::factory()->create();

        $this->service->assignUserToDefaultTeam($user);

        $user->refresh();
        $personalTeam = $user->ownedTeams()->where('personal_team', true)->first();
        $this->assertNotNull($personalTeam, 'Expected a personal team fallback to be created.');
        $this->assertSame($personalTeam->id, $user->current_team_id);
    }
}
