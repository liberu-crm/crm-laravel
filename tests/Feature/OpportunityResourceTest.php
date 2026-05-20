<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Opportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OpportunityResourceTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->withPersonalTeam()->create();
        $user->current_team_id = $user->ownedTeams->first()->id;
        $user->save();
        $this->actingAs($user);
    }

    public function test_opportunity_index_page_loads()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams->first();
        $user->current_team_id = $team->id;
        $user->save();

        $response = $this->actingAs($user)->get('/app/' . $team->id . '/opportunities');
        $this->assertTrue(
            in_array($response->status(), [200, 302]),
            "Expected /app/{team_id}/opportunities to return 200 or 302, got {$response->status()}"
        );
    }

    public function test_opportunity_model_can_be_created()
    {
        $opportunity = Opportunity::factory()->create([
            'deal_size' => 50000,
            'stage' => 'prospect',
        ]);

        $this->assertDatabaseHas('opportunities', [
            'opportunity_id' => $opportunity->opportunity_id,
            'deal_size' => 50000,
            'stage' => 'prospect',
        ]);
    }

    public function test_opportunity_model_can_be_updated()
    {
        $opportunity = Opportunity::factory()->create(['stage' => 'prospect']);

        $opportunity->update(['stage' => 'negotiation']);

        $this->assertDatabaseHas('opportunities', [
            'opportunity_id' => $opportunity->opportunity_id,
            'stage' => 'negotiation',
        ]);
    }
}
