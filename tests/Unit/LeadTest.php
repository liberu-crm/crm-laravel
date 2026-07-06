<?php

namespace Tests\Unit;

use App\Models\Activity;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_can_be_created(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'status' => 'new',
            'source' => 'referral',
            'potential_value' => 10000,
            'expected_close_date' => now()->addDays(30),
            'contact_id' => $contact->id,
            'user_id' => $user->id,
            'lifecycle_stage' => 'lead',
            'custom_fields' => ['industry' => 'Technology'],
        ]);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => 'new',
            'source' => 'referral',
            'potential_value' => 10000,
            'contact_id' => $contact->id,
            'user_id' => $user->id,
            'lifecycle_stage' => 'lead',
        ]);

        $this->assertEquals(['industry' => 'Technology'], $lead->custom_fields);
    }

    public function test_lead_belongs_to_contact(): void
    {
        $contact = Contact::factory()->create();
        $lead = Lead::factory()->create(['contact_id' => $contact->id]);

        $this->assertEquals($contact->id, $lead->contact->id);
    }

    public function test_lead_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $lead->user->id);
    }

    public function test_lead_has_many_activities(): void
    {
        $lead = Lead::factory()->create();
        $activity = Activity::factory()->create([
            'activitable_id' => $lead->id,
            'activitable_type' => Lead::class,
        ]);

        $this->assertTrue($lead->activities->contains($activity));
    }

    public function test_lead_score_calculation(): void
    {
        // Scoring rules: source referral +30, potential_value +min(30, value/1000),
        // lifecycle opportunity +20, contact +15, clamp 0..100. NOTE: the rules
        // rewrite dropped the old activity-engagement and mql/sql lifecycle
        // signals — revisit if product wants engagement-weighted scoring back.
        $lead = Lead::factory()->create([
            'source' => 'referral',
            'potential_value' => 50000,
            'lifecycle_stage' => 'opportunity',
            'contact_id' => null,
        ]);

        $score = $lead->calculateScore();

        // 30 (referral) + 30 (value, capped) + 20 (opportunity) = 80
        $this->assertEquals(80, $score);
        $this->assertEquals(80, $lead->score);
    }

    public function test_lead_custom_fields(): void
    {
        $lead = Lead::factory()->create([
            'custom_fields' => ['industry' => 'Technology', 'company_size' => '50-100'],
        ]);

        $this->assertEquals('Technology', $lead->custom_fields['industry']);
        $this->assertEquals('50-100', $lead->custom_fields['company_size']);
    }
}
