<?php

namespace Tests\Feature\Api;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Bulk-assign must only accept an assignee who is a member of the caller's
 * current team. Assigning your team's records to a user in another team (or a
 * teamless user) is a cross-tenant data-integrity hole; the endpoint must
 * reject it and leave the records untouched.
 */
class BulkAssignAuthzTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    /** A real member of $team (not its owner). */
    private function teammate(User $owner): User
    {
        $mate = User::factory()->create();
        $mate->teams()->attach($owner->currentTeam, ['role' => 'member']);

        return $mate;
    }

    /** Owns their own team; not a member of the caller's team. */
    private function outsider(): User
    {
        return User::factory()->withPersonalTeam()->create();
    }

    // --------------------------------------------------------------- deals

    public function test_deals_assign_to_same_team_member_succeeds(): void
    {
        $user = $this->actingUser();
        $mate = $this->teammate($user);
        $deal = Deal::factory()->create(['team_id' => $user->currentTeam->id, 'user_id' => null]);

        $this->postJson('/api/v1/deals/bulk/assign', [
            'ids' => [$deal->id],
            'user_id' => $mate->id,
        ])
            ->assertOk()
            ->assertJson(['assigned' => 1]);

        $this->assertDatabaseHas('deals', ['id' => $deal->id, 'user_id' => $mate->id]);
    }

    public function test_deals_assign_to_other_team_user_is_rejected(): void
    {
        $user = $this->actingUser();
        $outsider = $this->outsider();
        $deal = Deal::factory()->create(['team_id' => $user->currentTeam->id, 'user_id' => null]);

        $this->postJson('/api/v1/deals/bulk/assign', [
            'ids' => [$deal->id],
            'user_id' => $outsider->id,
        ])->assertForbidden();

        $this->assertDatabaseMissing('deals', ['id' => $deal->id, 'user_id' => $outsider->id]);
    }

    // --------------------------------------------------------------- tasks

    public function test_tasks_assign_to_same_team_member_succeeds(): void
    {
        $user = $this->actingUser();
        $mate = $this->teammate($user);
        $task = Task::factory()->create(['team_id' => $user->currentTeam->id, 'assigned_to' => null]);

        $this->postJson('/api/v1/tasks/bulk/assign', [
            'ids' => [$task->id],
            'user_id' => $mate->id,
        ])
            ->assertOk()
            ->assertJson(['assigned' => 1]);

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'assigned_to' => $mate->id]);
    }

    public function test_tasks_assign_to_other_team_user_is_rejected(): void
    {
        $user = $this->actingUser();
        $outsider = $this->outsider();
        $task = Task::factory()->create(['team_id' => $user->currentTeam->id, 'assigned_to' => null]);

        $this->postJson('/api/v1/tasks/bulk/assign', [
            'ids' => [$task->id],
            'user_id' => $outsider->id,
        ])->assertForbidden();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id, 'assigned_to' => $outsider->id]);
    }

    // ------------------------------------------------------------ contacts

    // Contacts have no assignee column (see summary — needs a migration), so
    // only the security property is asserted: a cross-team assignee is refused
    // before any write runs, and the record is left intact.
    public function test_contacts_assign_to_other_team_user_is_rejected(): void
    {
        $user = $this->actingUser();
        $outsider = $this->outsider();
        $contact = Contact::factory()->create(['team_id' => $user->currentTeam->id]);

        $this->postJson('/api/v1/contacts/bulk/assign', [
            'ids' => [$contact->id],
            'user_id' => $outsider->id,
        ])->assertForbidden();

        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }
}
