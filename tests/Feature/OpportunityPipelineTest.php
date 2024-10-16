<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Deal;
use App\Models\Stage;
use App\Models\Pipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Livewire\Livewire;

class OpportunityPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_pipeline_view_can_be_rendered()
    {
        $user = User::factory()->create();
        $pipeline = Pipeline::factory()->create(['is_active' => true]);
        $stages = Stage::factory(3)->create(['pipeline_id' => $pipeline->id]);
        $deals = Deal::factory(5)->create(['pipeline_id' => $pipeline->id, 'stage_id' => $stages->first()->id]);

        $response = $this->actingAs($user)->get('/admin/opportunities');

        $response->assertStatus(200);
        $response->assertSeeLivewire('opportunity-pipeline');
    }

    public function test_deal_can_be_moved_to_different_stage()
    {
        $user = User::factory()->create();
        $pipeline = Pipeline::factory()->create(['is_active' => true]);
        $stages = Stage::factory(3)->create(['pipeline_id' => $pipeline->id]);
        $deal = Deal::factory()->create(['pipeline_id' => $pipeline->id, 'stage_id' => $stages->first()->id]);

        Livewire::actingAs($user)
            ->test('opportunity-pipeline')
            ->call('updateDealStage', $deal->id, $stages->last()->id);

        $this->assertDatabaseHas('deals', [
            'id' => $deal->id,
            'stage_id' => $stages->last()->id,
        ]);
    }
}