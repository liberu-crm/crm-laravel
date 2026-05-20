<?php

namespace Tests\Unit;

use App\Models\Lead;
use App\Services\LeadScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $leadScoringService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->leadScoringService = new LeadScoringService();
    }

    public function test_score_calculation_scenarios(): void
    {
        $lead1 = Lead::factory()->create([
            'potential_value' => 10000,
            'lifecycle_stage' => 'lead',
        ]);

        $lead2 = Lead::factory()->create([
            'potential_value' => 50000,
            'lifecycle_stage' => 'opportunity',
        ]);

        $this->leadScoringService->scoreLeads($lead1);
        $this->leadScoringService->scoreLeads($lead2);

        $this->assertGreaterThan($lead1->score, $lead2->score);
        $this->assertDatabaseHas('leads', [
            'id' => $lead1->id,
            'score' => $lead1->score,
        ]);
        $this->assertDatabaseHas('leads', [
            'id' => $lead2->id,
            'score' => $lead2->score,
        ]);
    }

    public function test_recalculate_all_scores(): void
    {
        Lead::factory()->count(10)->create();

        $this->leadScoringService->recalculateAllScores();

        $leads = Lead::all();
        foreach ($leads as $lead) {
            $this->assertNotNull($lead->score);
            $this->assertGreaterThanOrEqual(0, $lead->score);
        }
    }
}