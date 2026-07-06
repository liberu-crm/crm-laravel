<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Models\Lead;
use App\Services\LeadScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeadScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $leadScoringService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->leadScoringService = new LeadScoringService;
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

    /**
     * source: referral +30 vs the "otherwise" +5 default -> the isolated gap is 25.
     * (Everything else is held at zero so the score is purely the source term.)
     */
    public function test_referral_source_contributes_its_documented_weight(): void
    {
        $base = [
            'potential_value' => 0,
            'contact_id' => null,
            'lifecycle_stage' => 'subscriber', // valid stage, not in the scoring map -> 0
        ];

        $referral = Lead::factory()->create($base + ['source' => 'referral']);
        $other = Lead::factory()->create($base + ['source' => 'other']);

        $this->leadScoringService->scoreLeads($referral);
        $this->leadScoringService->scoreLeads($other);

        $this->assertSame(30, $referral->score);
        $this->assertSame(5, $other->score);
        $this->assertSame(25, $referral->score - $other->score);
    }

    /**
     * contact present: +15 over an otherwise-identical lead with no contact.
     */
    public function test_contact_presence_adds_15(): void
    {
        $base = [
            'source' => 'other',
            'potential_value' => 0,
            'lifecycle_stage' => 'subscriber',
        ];

        $withoutContact = Lead::factory()->create($base + ['contact_id' => null]);
        $withContact = Lead::factory()->create($base + ['contact_id' => Contact::factory()->create()->id]);

        $this->leadScoringService->scoreLeads($withoutContact);
        $this->leadScoringService->scoreLeads($withContact);

        $this->assertSame($withoutContact->score + 15, $withContact->score);
    }

    /**
     * Every term at its maximum: referral (30) + value cap (30) + contact (15)
     * + customer (25) = 100, and the clamp holds it at 100.
     */
    public function test_maxed_out_lead_clamps_to_100(): void
    {
        $lead = Lead::factory()->create([
            'source' => 'referral',
            'potential_value' => 5_000_000, // floor(/1000) = 5000, capped at 30
            'contact_id' => Contact::factory()->create()->id,
            'lifecycle_stage' => 'opportunity', // placeholder; the setter rejects 'customer'
        ]);

        // 'customer' is a scored stage but is NOT in Lead::LIFECYCLE_STAGES, whose
        // mutator throws on it, so set it past the mutator via the query builder.
        DB::table('leads')->where('id', $lead->id)->update(['lifecycle_stage' => 'customer']);
        $lead->refresh();

        $this->leadScoringService->scoreLeads($lead);

        $this->assertSame(100, $lead->score);
    }

    /**
     * A bare lead (no matching source, no value, no contact, unscored stage)
     * scores the documented minimum: the +5 "otherwise" source floor.
     */
    public function test_bare_lead_scores_documented_minimum(): void
    {
        $lead = Lead::factory()->create([
            'source' => 'other',
            'potential_value' => 0,
            'contact_id' => null,
            'lifecycle_stage' => 'subscriber',
        ]);

        $this->leadScoringService->scoreLeads($lead);

        $this->assertSame(5, $lead->score);
    }
}
