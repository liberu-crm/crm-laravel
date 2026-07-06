<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Enums\Role;
use App\Models\Deal;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamManagementService;
use App\Support\TenantContext;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Events\TeamMemberAdded;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * F4 per-team RBAC: a role assignment is scoped to a team, so a user can hold
 * different roles in different teams. super_admin is the one global (platform)
 * role and answers true in every team.
 */
class PerTeamRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class); // global (team_id = null) role definitions
    }

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    private function setTeam(?Team $team): void
    {
        setPermissionsTeamId($team?->getKey());
    }

    private function assignInTeam(User $user, Team $team, Role $role): void
    {
        $this->setTeam($team);
        $user->assignRole($role->value);
    }

    private function hasRoleInTeam(User $user, ?Team $team, Role $role): bool
    {
        $this->setTeam($team);
        $user->unsetRelation('roles'); // relation is cached per team; refresh after a context switch

        return $user->hasRole($role);
    }

    public function test_a_user_holds_different_roles_in_different_teams(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $user = User::factory()->create();

        $this->assignInTeam($user, $teamA, Role::Manager);
        $this->assignInTeam($user, $teamB, Role::SalesRep);

        // In team A the user is a manager, not a sales rep.
        $this->assertTrue($this->hasRoleInTeam($user, $teamA, Role::Manager));
        $this->assertFalse($this->hasRoleInTeam($user, $teamA, Role::SalesRep));

        // In team B the user is a sales rep, not a manager.
        $this->assertTrue($this->hasRoleInTeam($user, $teamB, Role::SalesRep));
        $this->assertFalse($this->hasRoleInTeam($user, $teamB, Role::Manager));
    }

    public function test_a_manager_in_team_a_has_no_role_in_team_b(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $user = User::factory()->create();

        $this->assignInTeam($user, $teamA, Role::Manager);

        $this->assertTrue($this->hasRoleInTeam($user, $teamA, Role::Manager));
        $this->assertFalse($this->hasRoleInTeam($user, $teamB, Role::Manager));
        $this->assertFalse($this->hasRoleInTeam($user, $teamB, Role::Admin));
        $this->assertFalse($this->hasRoleInTeam($user, $teamB, Role::SalesRep));
    }

    public function test_owner_scope_follows_the_per_team_role(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        // Same person: manager in A (sees all), sales_rep in B (own only).
        $user = User::factory()->create();
        $this->assignInTeam($user, $teamA, Role::Manager);
        $this->assignInTeam($user, $teamB, Role::SalesRep);

        $mate = User::factory()->create();

        $myDealA = Deal::factory()->create(['team_id' => $teamA->id, 'user_id' => $user->id]);
        $mateDealA = Deal::factory()->create(['team_id' => $teamA->id, 'user_id' => $mate->id]);
        $myDealB = Deal::factory()->create(['team_id' => $teamB->id, 'user_id' => $user->id]);
        $mateDealB = Deal::factory()->create(['team_id' => $teamB->id, 'user_id' => $mate->id]);

        $this->actingAs($user);

        // Team A: manager → sees every deal in the team.
        TenantContext::set($teamA->id);
        $this->hasRoleInTeam($user, $teamA, Role::Manager); // set permission team + refresh roles
        $idsInA = Deal::pluck('id');
        $this->assertTrue($idsInA->contains($myDealA->id));
        $this->assertTrue($idsInA->contains($mateDealA->id), 'manager must see teammate deal in team A');

        // Team B: sales_rep → sees only their own deal.
        TenantContext::set($teamB->id);
        $this->hasRoleInTeam($user, $teamB, Role::SalesRep);
        $idsInB = Deal::pluck('id');
        $this->assertTrue($idsInB->contains($myDealB->id));
        $this->assertFalse($idsInB->contains($mateDealB->id), 'sales_rep must NOT see teammate deal in team B');
    }

    public function test_super_admin_is_true_across_all_teams_regardless_of_context(): void
    {
        $memberTeam = Team::factory()->create();
        $strangerTeam = Team::factory()->create(); // super admin has NO assignment here
        $user = User::factory()->create();

        // Global assignment: team_id = null.
        $this->setTeam(null);
        $user->assignRole(Role::SuperAdmin->value);

        // True with no team context.
        $this->assertTrue($this->hasRoleInTeam($user, null, Role::SuperAdmin));
        // True in a team the user belongs to.
        $this->assertTrue($this->hasRoleInTeam($user, $memberTeam, Role::SuperAdmin));
        // True even in a team the user has no assignment in — platform-wide.
        $this->assertTrue($this->hasRoleInTeam($user, $strangerTeam, Role::SuperAdmin));

        // The global row really is stored with team_id = null.
        $this->assertDatabaseHas('model_has_roles', [
            'model_id' => $user->id,
            'model_type' => $user->getMorphClass(),
            'team_id' => null,
        ]);
    }

    public function test_super_admin_does_not_leak_into_other_role_names(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();

        $this->setTeam(null);
        $user->assignRole(Role::SuperAdmin->value);

        // Being global super_admin must not make the user a manager anywhere.
        $this->assertFalse($this->hasRoleInTeam($user, $team, Role::Manager));
    }

    public function test_team_creator_becomes_admin_only_in_that_team(): void
    {
        $service = app(TeamManagementService::class);
        $creator = User::factory()->create(['name' => 'Ada']);
        $otherTeam = Team::factory()->create();

        $team = $service->createPersonalTeamForUser($creator);

        $this->assertTrue($this->hasRoleInTeam($creator, $team, Role::Admin));
        $this->assertFalse($this->hasRoleInTeam($creator, $otherTeam, Role::Admin));
    }

    public function test_added_member_becomes_sales_rep_in_that_team(): void
    {
        $service = app(TeamManagementService::class);
        $team = Team::factory()->create(['personal_team' => false]);
        $member = User::factory()->create();

        $service->assignUserToTeam($member, $team);

        $this->assertTrue($this->hasRoleInTeam($member, $team, Role::SalesRep));
        $this->assertFalse($this->hasRoleInTeam($member, $team, Role::Admin));
    }

    public function test_invited_member_becomes_sales_rep_via_team_member_added_event(): void
    {
        $team = Team::factory()->create();
        $invited = User::factory()->create();

        // Jetstream fires this on addTeamMember (also on invitation acceptance).
        event(new TeamMemberAdded($team, $invited));

        $this->assertTrue($this->hasRoleInTeam($invited, $team, Role::SalesRep));
    }
}
