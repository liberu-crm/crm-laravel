<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ContactPolicy authorizes a Contact when it belongs to the user's current team
 * ($contact->belongsToTeam($user->currentTeam?->getKey())) — the same idiom the
 * controllers and sibling record policies use. currentTeam resolves via the real
 * current_team_id column (there is no team_id column on users).
 */
class ContactPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_team_user_may_view_update_delete_restore_force_delete(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $user->current_team_id = $team->id;

        $contact = Contact::factory()->create();
        $contact->team_id = $team->id;

        $this->assertTrue($user->can('view', $contact));
        $this->assertTrue($user->can('update', $contact));
        $this->assertTrue($user->can('delete', $contact));
        $this->assertTrue($user->can('restore', $contact));
        $this->assertTrue($user->can('forceDelete', $contact));
    }

    public function test_other_team_user_is_denied(): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();

        $user = User::factory()->create();
        $user->current_team_id = $otherTeam->id;

        $contact = Contact::factory()->create();
        $contact->team_id = $team->id;

        $this->assertFalse($user->can('view', $contact));
        $this->assertFalse($user->can('update', $contact));
        $this->assertFalse($user->can('delete', $contact));
        $this->assertFalse($user->can('restore', $contact));
        $this->assertFalse($user->can('forceDelete', $contact));
    }
}
