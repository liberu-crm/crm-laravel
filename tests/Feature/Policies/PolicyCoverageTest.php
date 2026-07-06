<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\ConnectedAccount;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Coverage for two previously untested policies:
 *
 * - ConnectedAccountPolicy: a ConnectedAccount belongs to a User via user_id;
 *   only that user may view/update/delete it.
 * - TeamPolicy: Jetstream authorization — view is gated by belongsToTeam,
 *   the mutating actions by ownsTeam.
 */
class PolicyCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_connected_account_owner_may_view_update_delete(): void
    {
        $owner = User::factory()->create();
        $account = ConnectedAccount::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($owner->can('view', $account));
        $this->assertTrue($owner->can('update', $account));
        $this->assertTrue($owner->can('delete', $account));
    }

    public function test_connected_account_non_owner_is_denied(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $account = ConnectedAccount::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($other->can('view', $account));
        $this->assertFalse($other->can('update', $account));
        $this->assertFalse($other->can('delete', $account));
    }

    public function test_team_owner_may_view_and_manage(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($owner->can('view', $team));
        $this->assertTrue($owner->can('update', $team));
        $this->assertTrue($owner->can('delete', $team));
        $this->assertTrue($owner->can('addTeamMember', $team));
        $this->assertTrue($owner->can('updateTeamMember', $team));
        $this->assertTrue($owner->can('removeTeamMember', $team));
    }

    public function test_team_member_may_view_but_not_manage(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $owner->id]);
        $team->users()->attach($member, ['role' => 'editor']);

        $this->assertTrue($member->can('view', $team));

        $this->assertFalse($member->can('update', $team));
        $this->assertFalse($member->can('delete', $team));
        $this->assertFalse($member->can('addTeamMember', $team));
        $this->assertFalse($member->can('removeTeamMember', $team));
    }

    public function test_team_stranger_is_denied(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($stranger->can('view', $team));
        $this->assertFalse($stranger->can('update', $team));
        $this->assertFalse($stranger->can('delete', $team));
        $this->assertFalse($stranger->can('addTeamMember', $team));
        $this->assertFalse($stranger->can('removeTeamMember', $team));
    }
}
