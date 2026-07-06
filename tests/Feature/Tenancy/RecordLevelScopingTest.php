<?php

namespace Tests\Feature\Tenancy;

use App\Models\Deal;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Support\AccessContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * F4 record-level scoping: within a team, sales_rep/free see only records they
 * own; manager/admin/super_admin see all team records. Owner column defaults to
 * user_id (Task overrides to assigned_to).
 */
class RecordLevelScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['super_admin', 'admin', 'manager', 'sales_rep', 'free'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    protected function tearDown(): void
    {
        AccessContext::clear();
        TenantContext::clear();
        parent::tearDown();
    }

    private function userWithRole(string $role, Team $team): User
    {
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_sales_rep_sees_only_own_deals(): void
    {
        $team = Team::factory()->create();
        $rep = $this->userWithRole('sales_rep', $team);
        $mate = $this->userWithRole('sales_rep', $team);
        $mine = Deal::factory()->create(['team_id' => $team->id, 'user_id' => $rep->id]);
        $theirs = Deal::factory()->create(['team_id' => $team->id, 'user_id' => $mate->id]);

        $this->actingAs($rep);
        $ids = Deal::pluck('id');

        $this->assertTrue($ids->contains($mine->id), 'rep must see own deal');
        $this->assertFalse($ids->contains($theirs->id), 'rep must NOT see teammate deal');
    }

    public function test_manager_sees_all_team_deals(): void
    {
        $team = Team::factory()->create();
        $manager = $this->userWithRole('manager', $team);
        $rep = $this->userWithRole('sales_rep', $team);
        Deal::factory()->create(['team_id' => $team->id, 'user_id' => $manager->id]);
        Deal::factory()->create(['team_id' => $team->id, 'user_id' => $rep->id]);

        $this->actingAs($manager);

        $this->assertCount(2, Deal::all());
    }

    public function test_task_scopes_by_assigned_to(): void
    {
        $team = Team::factory()->create();
        $rep = $this->userWithRole('sales_rep', $team);
        $other = $this->userWithRole('sales_rep', $team);
        $mine = Task::factory()->create(['team_id' => $team->id, 'assigned_to' => $rep->id]);
        $theirs = Task::factory()->create(['team_id' => $team->id, 'assigned_to' => $other->id]);

        $this->actingAs($rep);
        $ids = Task::pluck('id');

        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($theirs->id), 'rep must NOT see task assigned to someone else');
    }

    public function test_owner_scope_can_be_bypassed(): void
    {
        $team = Team::factory()->create();
        $rep = $this->userWithRole('sales_rep', $team);
        $mate = $this->userWithRole('sales_rep', $team);
        Deal::factory()->create(['team_id' => $team->id, 'user_id' => $rep->id]);
        Deal::factory()->create(['team_id' => $team->id, 'user_id' => $mate->id]);

        $this->actingAs($rep);

        $this->assertCount(2, Deal::withoutGlobalScope('owner')->get());
    }

    public function test_creating_stamps_owner_from_current_user(): void
    {
        $team = Team::factory()->create();
        $rep = $this->userWithRole('sales_rep', $team);
        $this->actingAs($rep);

        $deal = Deal::factory()->create(['team_id' => $team->id, 'user_id' => null]);

        $this->assertSame($rep->id, $deal->user_id, 'creator must be stamped as owner');
        $this->assertTrue(Deal::pluck('id')->contains($deal->id), 'rep must see the deal they just created');
    }
}
