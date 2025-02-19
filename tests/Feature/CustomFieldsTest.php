<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\CustomField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_create_custom_field()
    {
        $response = $this->actingAs($this->user)->post('/custom-fields', [
            'name' => 'Test Field',
            'type' => 'text',
            'model_type' => 'contact',
        ]);

        $response->assertRedirect('/custom-fields');
        $this->assertDatabaseHas('custom_fields', [
            'name' => 'Test Field',
            'type' => 'text',
            'model_type' => 'contact',
        ]);
    }

    public function test_user_can_search_contacts_with_custom_fields()
    {
        $customField = CustomField::factory()->create([
            'name' => 'Test Field',
            'type' => 'text',
            'model_type' => 'contact',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $contact1 = Contact::factory()->create([
            'custom_fields' => ['Test Field' => 'Unique Value'],
            'team_id' => $this->user->currentTeam->id,
        ]);

        $contact2 = Contact::factory()->create([
            'custom_fields' => ['Test Field' => 'Another Value'],
            'team_id' => $this->user->currentTeam->id,
        ]);

        $response = $this->actingAs($this->user)->get('/contacts?search=Unique Value');

        $response->assertSee($contact1->name);
        $response->assertDontSee($contact2->name);
    }

    public function test_user_can_search_leads_with_custom_fields()
    {
        $customField = CustomField::factory()->create([
            'name' => 'Test Field',
            'type' => 'text',
            'model_type' => 'lead',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $lead1 = Lead::factory()->create([
            'custom_fields' => ['Test Field' => 'Unique Value'],
            'team_id' => $this->user->currentTeam->id,
        ]);

        $lead2 = Lead::factory()->create([
            'custom_fields' => ['Test Field' => 'Another Value'],
            'team_id' => $this->user->currentTeam->id,
        ]);

        $response = $this->actingAs($this->user)->get('/leads?search=Unique Value');

        $response->assertSee($lead1->contact->name);
        $response->assertDontSee($lead2->contact->name);
    }

    public function test_custom_fields_are_included_in_contact_report()
    {
        $customField = CustomField::factory()->create([
            'name' => 'Test Field',
            'type' => 'text',
            'model_type' => 'contact',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $contact = Contact::factory()->create([
            'custom_fields' => ['Test Field' => 'Test Value'],
            'team_id' => $this->user->currentTeam->id,
        ]);

        $response = $this->actingAs($this->user)->get('/reports/contacts');

        $response->assertSee('Test Field');
        $response->assertSee('Test Value');
    }

    public function test_custom_fields_are_included_in_lead_report()
    {
        $customField = CustomField::factory()->create([
            'name' => 'Test Field',
            'type' => 'text',
            'model_type' => 'lead',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $lead = Lead::factory()->create([
            'custom_fields' => ['Test Field' => 'Test Value'],
            'team_id' => $this->user->currentTeam->id,
        ]);

        $response = $this->actingAs($this->user)->get('/reports/leads');

        $response->assertSee('Test Field');
        $response->assertSee('Test Value');
    }

    public function test_advanced_filtering_with_custom_fields_for_contacts()
    {
        $customField = CustomField::factory()->create([
            'name' => 'Priority',
            'type' => 'select',
            'model_type' => 'contact',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $contact1 = Contact::factory()->create([
            'custom_fields' => ['Priority' => 'High'],
            'team_id' => $this->user->currentTeam->id,
        ]);

        $contact2 = Contact::factory()->create([
            'custom_fields' => ['Priority' => 'Low'],
            'team_id' => $this->user->currentTeam->id,
        ]);

        $response = $this->actingAs($this->user)->get('/contacts?custom_fields[Priority]=High');

        $response->assertSee($contact1->name);
        $response->assertDontSee($contact2->name);
    }

    public function test_advanced_filtering_with_custom_fields_for_leads()
    {
        $customField = CustomField::factory()->create([
            'name' => 'Budget',
            'type' => 'number',
            'model_type' => 'lead',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $lead1 = Lead::factory()->create([
            'custom_fields' => ['Budget' => 10000],
            'team_id' => $this->user->currentTeam->id,
        ]);

        $lead2 = Lead::factory()->create([
            'custom_fields' => ['Budget' => 5000],
            'team_id' => $this->user->currentTeam->id,
        ]);

        $response = $this->actingAs($this->user)->get('/leads?custom_fields[Budget][min]=7000');

        $response->assertSee($lead1->contact->name);
        $response->assertDontSee($lead2->contact->name);

        $response->assertSee('Test Value');
    }
}

        