<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\Jetstream\AddTeamMember;
use App\Actions\Jetstream\DeleteUserWithTeams;
use App\Actions\Jetstream\InviteTeamMember;
use App\Actions\Jetstream\RemoveTeamMember;
use App\Enums\Role;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Jetstream\Mail\TeamInvitation as TeamInvitationMail;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Real behaviour of the wired Jetstream team-lifecycle actions. This app runs
 * Spatie teams mode: adding a member fires TeamMemberAdded, whose listener
 * (AssignDefaultTeamRole) grants the member sales_rep scoped to that team.
 */
class JetstreamActionsTest extends TestCase
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

    /** Owner + a non-personal team they own. */
    private function ownedTeam(): array
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $owner->id, 'personal_team' => false]);

        return [$owner, $team];
    }

    private function hasRoleInTeam(User $user, Team $team, Role $role): bool
    {
        setPermissionsTeamId($team->getKey());
        $user->unsetRelation('roles'); // relation is cached per team; refresh after a context switch

        return $user->hasRole($role);
    }

    public function test_add_team_member_attaches_user_and_grants_sales_rep(): void
    {
        [$owner, $team] = $this->ownedTeam();
        $member = User::factory()->create();

        app(AddTeamMember::class)->add($owner, $team, $member->email, 'admin');

        // Pivot membership carries the Jetstream role passed in.
        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'admin',
        ]);

        // TeamMemberAdded → AssignDefaultTeamRole granted sales_rep in this team.
        $this->assertTrue($this->hasRoleInTeam($member, $team, Role::SalesRep));
    }

    public function test_add_team_member_consumes_a_matching_invitation(): void
    {
        [$owner, $team] = $this->ownedTeam();
        $member = User::factory()->create();
        $team->teamInvitations()->create([
            'email' => $member->email,
            'role' => 'editor',
            'token' => 'tok-'.uniqid(),
        ]);

        app(AddTeamMember::class)->add($owner, $team, $member->email, 'admin');

        // Invitation role wins over the passed role, and the invite is deleted.
        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'editor',
        ]);
        $this->assertDatabaseMissing('team_invitations', [
            'team_id' => $team->id,
            'email' => $member->email,
        ]);
    }

    public function test_invite_team_member_creates_invitation_and_sends_mail(): void
    {
        Mail::fake();
        [$owner, $team] = $this->ownedTeam();

        app(InviteTeamMember::class)->invite($owner, $team, 'invitee@example.com', 'editor');

        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $team->id,
            'email' => 'invitee@example.com',
            'role' => 'editor',
        ]);
        Mail::assertSent(TeamInvitationMail::class, fn (TeamInvitationMail $mail): bool => $mail->hasTo('invitee@example.com'));
    }

    public function test_remove_team_member_detaches_the_membership(): void
    {
        [$owner, $team] = $this->ownedTeam();
        $member = User::factory()->create();
        $team->users()->attach($member, ['role' => 'admin']);

        app(RemoveTeamMember::class)->remove($owner, $team, $member);

        $this->assertDatabaseMissing('team_user', [
            'team_id' => $team->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_delete_user_with_teams_purges_owned_teams_and_the_user(): void
    {
        [$owner, $ownedTeam] = $this->ownedTeam();
        $member = User::factory()->create();
        $ownedTeam->users()->attach($member, ['role' => 'admin']);

        // A team the user only belongs to must survive (only the membership goes).
        [, $otherTeam] = $this->ownedTeam();
        $owner->teams()->attach($otherTeam, ['role' => 'admin']);

        app(DeleteUserWithTeams::class)->delete($owner);

        $this->assertModelMissing($owner);
        $this->assertModelMissing($ownedTeam);
        $this->assertDatabaseMissing('team_user', ['team_id' => $ownedTeam->id]);
        $this->assertDatabaseMissing('team_user', ['user_id' => $owner->id]);

        // Foreign team itself is untouched.
        $this->assertModelExists($otherTeam);
    }
}
