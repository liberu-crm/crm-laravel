<?php

namespace Tests\Feature\Api;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DealApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    private function foreignTeamId(): int
    {
        return User::factory()->withPersonalTeam()->create()->currentTeam->id;
    }

    public function test_api_requires_authentication(): void
    {
        $this->getJson('/api/v1/deals')->assertUnauthorized();
    }

    // -------------------------------------------------------------- index

    public function test_index_returns_only_own_team_deals(): void
    {
        $user = $this->actingUser();
        Deal::factory()->count(2)->create(['team_id' => $user->currentTeam->id]);
        Deal::factory()->create(['team_id' => $this->foreignTeamId()]);

        $this->getJson('/api/v1/deals')
            ->assertOk()
            ->assertJsonCount(2);
    }

    // --------------------------------------------------------------- show

    public function test_can_show_own_deal(): void
    {
        $user = $this->actingUser();
        $deal = Deal::factory()->create(['team_id' => $user->currentTeam->id]);

        $this->getJson("/api/v1/deals/{$deal->id}")
            ->assertOk()
            ->assertJsonFragment(['id' => $deal->id]);
    }

    public function test_cannot_show_other_team_deal_returns_404(): void
    {
        $this->actingUser();
        $deal = Deal::factory()->create(['team_id' => $this->foreignTeamId()]);

        // Tenant global scope hides foreign records, so binding 404s before the
        // controller runs — no disclosure that the record exists.
        $this->getJson("/api/v1/deals/{$deal->id}")->assertNotFound();
    }

    // -------------------------------------------------------------- store

    public function test_can_create_deal(): void
    {
        $user = $this->actingUser();

        $this->postJson('/api/v1/deals', [
            'name' => 'Big Deal',
            'value' => 5000,
            'stage' => 'prospect',
        ])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'Big Deal', 'stage' => 'prospect']);

        $this->assertDatabaseHas('deals', [
            'name' => 'Big Deal',
            'team_id' => $user->currentTeam->id,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingUser();

        $this->postJson('/api/v1/deals', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'value']);
    }

    public function test_store_validates_value_is_numeric(): void
    {
        $this->actingUser();

        $this->postJson('/api/v1/deals', ['name' => 'X', 'value' => 'not-a-number'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['value']);
    }

    // ------------------------------------------------------------- update

    public function test_can_update_own_deal(): void
    {
        $user = $this->actingUser();
        $deal = Deal::factory()->create(['team_id' => $user->currentTeam->id]);

        $this->putJson("/api/v1/deals/{$deal->id}", ['name' => 'Renamed'])
            ->assertOk()
            ->assertJsonFragment(['name' => 'Renamed']);

        $this->assertDatabaseHas('deals', ['id' => $deal->id, 'name' => 'Renamed']);
    }

    public function test_cannot_update_other_team_deal_returns_404(): void
    {
        $this->actingUser();
        $deal = Deal::factory()->create([
            'team_id' => $this->foreignTeamId(),
            'name' => 'Original',
        ]);

        $this->putJson("/api/v1/deals/{$deal->id}", ['name' => 'Hijacked'])
            ->assertNotFound();

        $this->assertDatabaseHas('deals', ['id' => $deal->id, 'name' => 'Original']);
    }

    public function test_update_validates_probability_range(): void
    {
        $user = $this->actingUser();
        $deal = Deal::factory()->create(['team_id' => $user->currentTeam->id]);

        $this->putJson("/api/v1/deals/{$deal->id}", ['probability' => 150])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['probability']);
    }

    // ------------------------------------------------------------ destroy

    public function test_can_delete_own_deal(): void
    {
        $user = $this->actingUser();
        $deal = Deal::factory()->create(['team_id' => $user->currentTeam->id]);

        $this->deleteJson("/api/v1/deals/{$deal->id}")->assertNoContent();

        $this->assertDatabaseMissing('deals', ['id' => $deal->id]);
    }

    public function test_cannot_delete_other_team_deal_returns_404(): void
    {
        $this->actingUser();
        $deal = Deal::factory()->create(['team_id' => $this->foreignTeamId()]);

        $this->deleteJson("/api/v1/deals/{$deal->id}")->assertNotFound();

        $this->assertDatabaseHas('deals', ['id' => $deal->id]);
    }

    // --------------------------------------------------------- bulk update

    public function test_bulk_update_updates_own_deals_stage(): void
    {
        $user = $this->actingUser();
        $deals = Deal::factory()->count(2)->create([
            'team_id' => $user->currentTeam->id,
            'stage' => 'prospect',
        ]);

        $this->postJson('/api/v1/deals/bulk/update', [
            'ids' => $deals->pluck('id')->all(),
            'data' => ['stage' => 'won'],
        ])
            ->assertOk()
            ->assertJson(['updated' => 2]);

        $this->assertDatabaseHas('deals', ['id' => $deals->first()->id, 'stage' => 'won']);
    }

    public function test_bulk_update_ignores_phantom_status_field(): void
    {
        // Regression: 'status' is not a column on deals (it lives on contacts).
        // It used to be in the allow-list, so a mass-update 500'd with an
        // unknown-column SQL error. Now it is dropped and returns a clean 422.
        $user = $this->actingUser();
        $deal = Deal::factory()->create(['team_id' => $user->currentTeam->id]);

        $this->postJson('/api/v1/deals/bulk/update', [
            'ids' => [$deal->id],
            'data' => ['status' => 'won'],
        ])->assertUnprocessable()
            ->assertJsonFragment(['message' => 'No valid fields to update.']);
    }

    public function test_bulk_update_does_not_touch_other_team_deals(): void
    {
        $user = $this->actingUser();
        $own = Deal::factory()->create(['team_id' => $user->currentTeam->id, 'stage' => 'prospect']);
        $foreign = Deal::factory()->create(['team_id' => $this->foreignTeamId(), 'stage' => 'prospect']);

        $this->postJson('/api/v1/deals/bulk/update', [
            'ids' => [$own->id, $foreign->id],
            'data' => ['stage' => 'won'],
        ])
            ->assertOk()
            ->assertJson(['updated' => 1]);

        $this->assertDatabaseHas('deals', ['id' => $foreign->id, 'stage' => 'prospect']);
    }

    public function test_bulk_update_validates_ids_required(): void
    {
        $this->actingUser();

        $this->postJson('/api/v1/deals/bulk/update', ['data' => ['stage' => 'won']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids']);
    }

    // --------------------------------------------------------- bulk delete

    public function test_bulk_delete_removes_only_own_deals(): void
    {
        $user = $this->actingUser();
        $own = Deal::factory()->create(['team_id' => $user->currentTeam->id]);
        $foreign = Deal::factory()->create(['team_id' => $this->foreignTeamId()]);

        $this->postJson('/api/v1/deals/bulk/delete', [
            'ids' => [$own->id, $foreign->id],
        ])
            ->assertOk()
            ->assertJson(['deleted' => 1]);

        $this->assertDatabaseMissing('deals', ['id' => $own->id]);
        $this->assertDatabaseHas('deals', ['id' => $foreign->id]);
    }

    // --------------------------------------------------------- bulk assign

    public function test_bulk_assign_sets_user_only_on_own_deals(): void
    {
        $user = $this->actingUser();
        // Distinct assignee so the foreign deal's pre-stamped creator (this
        // caller) can't masquerade as a successful cross-team assignment.
        $assignee = User::factory()->create();
        // Bulk-assign now rejects cross-team/teamless assignees, so the
        // assignee must be a member of the caller's team.
        $assignee->teams()->attach($user->currentTeam, ['role' => 'member']);
        $own = Deal::factory()->create(['team_id' => $user->currentTeam->id]);
        $foreign = Deal::factory()->create(['team_id' => $this->foreignTeamId()]);

        $this->postJson('/api/v1/deals/bulk/assign', [
            'ids' => [$own->id, $foreign->id],
            'user_id' => $assignee->id,
        ])
            ->assertOk()
            ->assertJson(['assigned' => 1]);

        $this->assertDatabaseHas('deals', ['id' => $own->id, 'user_id' => $assignee->id]);
        $this->assertDatabaseMissing('deals', ['id' => $foreign->id, 'user_id' => $assignee->id]);
    }
}
