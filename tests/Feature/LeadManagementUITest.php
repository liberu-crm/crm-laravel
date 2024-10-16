<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Lead;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadManagementUITest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_view_lead_list()
    {
        $leads = Lead::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->get('/leads');

        $response->assertStatus(200);
        foreach ($leads as $lead) {
            $response->assertSee($lead->contact->name);
        }
    }

    public function test_lead_search_and_filtering()
    {
        $lead1 = Lead::factory()->create([
            'status' => 'new',
            'source' => 'website',
            'potential_value' => 10000,
        ]);
        $lead2 = Lead::factory()->create([
            'status' => 'qualified',
            'source' => 'referral',
            'potential_value' => 50000,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/leads?status=new&source=website&potential_value_min=5000');

        $response->assertStatus(200);
        $response->assertSee($lead1->contact->name);
        $response->assertDontSee($lead2->contact->name);
    }

    public function test_lead_lifecycle_stage_transition()
    {
        $lead = Lead::factory()->create(['lifecycle_stage' => 'lead']);

        $response = $this->actingAs($this->user)
            ->patch("/leads/{$lead->id}/advance-stage");

        $response->assertStatus(200);
        $this->assertEquals('marketing_qualified_lead', $lead->fresh()->lifecycle_stage);
    }

    public function test_lead_advanced_search()
    {
        $contact1 = Contact::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $contact2 = Contact::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $lead1 = Lead::factory()->create(['contact_id' => $contact1->id, 'status' => 'new']);
        $lead2 = Lead::factory()->create(['contact_id' => $contact2->id, 'status' => 'qualified']);

        $response = $this->actingAs($this->user)
            ->get('/leads/search?query=john');

        $response->assertStatus(200);
        $response->assertSee($lead1->contact->name);
        $response->assertDontSee($lead2->contact->name);
    }

    public function test_lead_full_text_search()
    {
        $contact1 = Contact::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $contact2 = Contact::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $lead1 = Lead::factory()->create([
            'contact_id' => $contact1->id,
            'status' => 'new',
            'source' => 'website',
            'lifecycle_stage' => 'lead'
        ]);
        $lead2 = Lead::factory()->create([
            'contact_id' => $contact2->id,
            'status' => 'qualified',
            'source' => 'referral',
            'lifecycle_stage' => 'opportunity'
        ]);

        $response = $this->actingAs($this->user)
            ->get('/leads?search=website');

        $response->assertStatus(200);
        $response->assertSee($lead1->contact->name);
        $response->assertDontSee($lead2->contact->name);
    }

    public function test_lead_advanced_filtering()
    {
        $lead1 = Lead::factory()->create([
            'status' => 'new',
            'source' => 'website',
            'potential_value' => 10000,
            'expected_close_date' => now()->addDays(30),
        ]);
        $lead2 = Lead::factory()->create([
            'status' => 'qualified',
            'source' => 'referral',
            'potential_value' => 50000,
            'expected_close_date' => now()->addDays(60),
        ]);

        $response = $this->actingAs($this->user)
            ->get('/leads?status=new&source=website&potential_value_min=5000&potential_value_max=20000&expected_close_date_start=' . now()->toDateString() . '&expected_close_date_end=' . now()->addDays(45)->toDateString());

        $response->assertStatus(200);
        $response->assertSee($lead1->contact->name);
        $response->assertDontSee($lead2->contact->name);
    }
}