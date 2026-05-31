<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\CustomField;
use App\Models\Lead;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->team = Team::factory()->create();
        $this->user = User::factory()->create(['current_team_id' => $this->team->id]);
    }

    public function test_user_can_create_custom_field(): void
    {
        CustomField::factory()->create([
            'name' => 'Test Field',
            'type' => 'text',
            'model_type' => \App\Models\Contact::class,
            'team_id' => $this->team->id,
        ]);

        $this->assertDatabaseHas('custom_fields', [
            'name' => 'Test Field',
            'type' => 'text',
            'model_type' => \App\Models\Contact::class,
        ]);
    }

    public function test_contact_can_have_custom_fields(): void
    {
        $contact = Contact::factory()->create([
            'custom_fields' => ['Test Field' => 'Unique Value'],
            'team_id' => $this->team->id,
        ]);

        $this->assertEquals('Unique Value', $contact->custom_fields['Test Field']);
    }

    public function test_lead_can_have_custom_fields(): void
    {
        $lead = Lead::factory()->create([
            'custom_fields' => ['Industry' => 'Technology'],
        ]);

        $this->assertEquals('Technology', $lead->custom_fields['Industry']);
    }

    public function test_custom_field_can_be_updated(): void
    {
        $customField = CustomField::factory()->create([
            'name' => 'Old Name',
            'type' => 'text',
            'model_type' => \App\Models\Contact::class,
            'team_id' => $this->team->id,
        ]);

        $customField->update(['name' => 'New Name']);

        $this->assertDatabaseHas('custom_fields', [
            'id' => $customField->id,
            'name' => 'New Name',
        ]);
    }

    public function test_custom_field_can_be_deleted(): void
    {
        $customField = CustomField::factory()->create([
            'team_id' => $this->team->id,
        ]);

        $customField->delete();

        $this->assertDatabaseMissing('custom_fields', ['id' => $customField->id]);
    }

    public function test_contacts_with_custom_fields_can_be_filtered(): void
    {
        $contact1 = Contact::factory()->create([
            'custom_fields' => ['Priority' => 'High'],
            'team_id' => $this->team->id,
        ]);

        $contact2 = Contact::factory()->create([
            'custom_fields' => ['Priority' => 'Low'],
            'team_id' => $this->team->id,
        ]);

        $results = Contact::whereJsonContains('custom_fields->Priority', 'High')->get();

        $this->assertTrue($results->contains($contact1));
        $this->assertFalse($results->contains($contact2));
    }

    public function test_leads_with_custom_fields_can_be_filtered(): void
    {
        $lead1 = Lead::factory()->create([
            'custom_fields' => ['Budget' => 10000],
        ]);

        $lead2 = Lead::factory()->create([
            'custom_fields' => ['Budget' => 5000],
        ]);

        $results = Lead::whereJsonContains('custom_fields->Budget', 10000)->get();

        $this->assertTrue($results->contains($lead1));
        $this->assertFalse($results->contains($lead2));
    }
}
