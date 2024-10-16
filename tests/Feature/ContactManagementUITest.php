<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Contact;
use App\Models\CustomField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactManagementUITest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ... (keep all existing tests)

    public function test_custom_fields_are_displayed_in_contact_list()
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

        $response = $this->actingAs($this->user)->get('/contacts');

        $response->assertStatus(200);
        $response->assertSee('Test Field');
        $response->assertSee('Test Value');
        $response->assertSee('Upload Document');
    }

    public function test_custom_fields_are_editable_in_contact_form()
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

        $response = $this->actingAs($this->user)->get("/contacts/{$contact->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('Test Field');
        $response->assertSee('Test Value');
        $response->assertSee('Documents');

        $updatedData = [
            'name' => $contact->name,
            'email' => $contact->email,
            'custom_fields' => ['Test Field' => 'Updated Value'],
        ];

        $response = $this->actingAs($this->user)->put("/contacts/{$contact->id}", $updatedData);

        $response->assertRedirect('/contacts');
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'custom_fields->Test Field' => 'Updated Value',
        ]);
    }

    public function test_custom_fields_are_searchable_in_contact_list()
    {
        $customField = CustomField::factory()->create([
            'name' => 'Test Field',
            'type' => 'text',
            'model_type' => 'contact',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $contact1 = Contact::factory()->create([
            'custom_fields' => ['Test Field' => 'Searchable Value'],
            'team_id' => $this->user->currentTeam->id,
        ]);

        $contact2 = Contact::factory()->create([
            'custom_fields' => ['Test Field' => 'Other Value'],
            'team_id' => $this->user->currentTeam->id,
        ]);

        $response = $this->actingAs($this->user)->get('/contacts?search=Searchable');

        $response->assertStatus(200);
        $response->assertSee($contact1->name);
        $response->assertDontSee($contact2->name);
    }

    public function test_custom_fields_are_included_in_contact_export()
    {
        $customField = CustomField::factory()->create([
            'name' => 'Test Field',
            'type' => 'text',
            'model_type' => 'contact',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $contact = Contact::factory()->create([
            'custom_fields' => ['Test Field' => 'Export Value'],
            'team_id' => $this->user->currentTeam->id,
        ]);

        $response = $this->actingAs($this->user)->get('/contacts/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('Test Field');
        $response->assertSee('Export Value');
        $response->assertSee('Documents');
    }

    public function test_user_can_upload_document_for_contact()
    {
        $contact = Contact::factory()->create([
            'team_id' => $this->user->currentTeam->id,
        ]);

        Storage::fake('public');
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->user)->post("/contacts/{$contact->id}/upload-document", [
            'file' => $file,
        ]);

        $response->assertRedirect("/contacts/{$contact->id}");
        Storage::disk('public')->assertExists('documents/' . $file->hashName());
        $this->assertDatabaseHas('documents', [
            'documentable_id' => $contact->id,
            'documentable_type' => Contact::class,
        ]);
    }
}