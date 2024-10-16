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

        $contact = Contact::factory()->create([
            'custom_fields' => ['Test Field' => 'Test Value'],
            'team_id' => $this->user->currentTeam->id,
        ]);

        $response = $this->actingAs($this->user)->get('/contacts?search=Test Value');

        $response->assertSee($contact->name);
    }

    public function test_user_can_search_leads_with_custom_fields()
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

        $response = $this->actingAs($this->user)->get('/leads?search=Test Value');

        $response->assertSee($lead->name);
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
}