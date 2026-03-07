<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Lead;
use App\Models\Contact;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadManagementUITest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->user->current_team_id = $this->user->ownedTeams->first()->id;
        $this->user->save();
    }

    public function test_create_and_retrieve_lead_with_custom_fields()
    {
        $contact = Contact::factory()->create();
        $customFields = ['industry' => 'Technology', 'company_size' => '50-100'];

        $lead = Lead::factory()->create([
            'contact_id' => $contact->id,
            'custom_fields' => $customFields,
        ]);

        $this->assertEquals($customFields, $lead->fresh()->custom_fields);
        $this->assertEquals($contact->id, $lead->contact_id);
    }

    public function test_lead_index_page_loads()
    {
        $response = $this->actingAs($this->user)->get('/app/leads');
        $response->assertSuccessful();
    }

    public function test_lead_can_be_created_via_model()
    {
        $lead = Lead::factory()->create([
            'status' => 'new',
            'lifecycle_stage' => 'lead',
        ]);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => 'new',
        ]);
    }

    public function test_lead_score_can_be_calculated()
    {
        $lead = Lead::factory()->create([
            'lifecycle_stage' => 'sales_qualified_lead',
            'potential_value' => 50000,
        ]);

        $score = $lead->calculateScore();
        $this->assertIsInt($score);
        $this->assertGreaterThan(0, $score);
    }
}
