<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Deal;
use App\Models\Stage;
use App\Models\Pipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OpportunityPipelineTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_pipeline_can_be_created_with_stages()
    {
        $pipeline = Pipeline::factory()->create(['is_active' => true]);
        $stages = Stage::factory(3)->create(['pipeline_id' => $pipeline->id]);

        $this->assertDatabaseHas('pipelines', ['id' => $pipeline->id, 'is_active' => true]);
        $this->assertEquals(3, $pipeline->stages()->count());
    }

    public function test_deals_can_be_associated_with_pipeline_stages()
    {
        $pipeline = Pipeline::factory()->create(['is_active' => true]);
        $stages = Stage::factory(3)->create(['pipeline_id' => $pipeline->id]);
        $deals = Deal::factory(5)->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stages->first()->id,
        ]);

        $this->assertDatabaseHas('deals', [
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stages->first()->id,
        ]);
        $this->assertEquals(5, Deal::where('pipeline_id', $pipeline->id)->count());
    }

    public function test_deal_can_be_moved_to_different_stage()
    {
        $user = User::factory()->create();
        $pipeline = Pipeline::factory()->create(['is_active' => true]);
        $stages = Stage::factory(3)->create(['pipeline_id' => $pipeline->id]);
        $deal = Deal::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stages->first()->id,
        ]);

        $deal->stage_id = $stages->last()->id;
        $deal->save();

        $this->assertDatabaseHas('deals', [
            'id' => $deal->id,
            'stage_id' => $stages->last()->id,
        ]);
    }

    public function test_opportunity_index_page_loads_for_authenticated_user()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->current_team_id = $user->ownedTeams->first()->id;
        $user->save();

        $response = $this->actingAs($user)->get('/app/opportunities');
        $response->assertSuccessful();
    }
}
