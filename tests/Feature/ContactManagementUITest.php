<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Contact;
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

    public function test_user_can_view_contact_list()
    {
        $contacts = Contact::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->get('/contacts');

        $response->assertStatus(200);
        foreach ($contacts as $contact) {
            $response->assertSee($contact->name);
        }
    }

    public function test_user_can_create_contact()
    {
        $contactData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->user)
            ->post('/contacts', $contactData);

        $response->assertRedirect('/contacts');
        $this->assertDatabaseHas('contacts', $contactData);
    }

    public function test_user_can_edit_contact()
    {
        $contact = Contact::factory()->create();
        $updatedData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '9876543210',
            'status' => 'inactive',
        ];

        $response = $this->actingAs($this->user)
            ->put("/contacts/{$contact->id}", $updatedData);

        $response->assertRedirect('/contacts');
        $this->assertDatabaseHas('contacts', $updatedData);
    }

    public function test_user_can_delete_contact()
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete("/contacts/{$contact->id}");

        $response->assertRedirect('/contacts');
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_contact_list_has_pagination()
    {
        Contact::factory()->count(25)->create();

        $response = $this->actingAs($this->user)
            ->get('/contacts');

        $response->assertStatus(200);
        $response->assertSee('Next');
        $response->assertDontSee(Contact::orderBy('id', 'desc')->first()->name);
    }

    public function test_contact_list_has_search_functionality()
    {
        $searchContact = Contact::factory()->create(['name' => 'Searchable Name']);
        Contact::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->get('/contacts?search=Searchable');

        $response->assertStatus(200);
        $response->assertSee($searchContact->name);
        $response->assertDontSee(Contact::where('name', '!=', 'Searchable Name')->first()->name);
    }

    public function test_contact_form_has_validation_errors()
    {
        $response = $this->actingAs($this->user)
            ->post('/contacts', []);

        $response->assertSessionHasErrors(['name', 'email', 'status']);
    }

    public function test_contact_list_has_quick_actions()
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->user)
            ->get('/contacts');

        $response->assertStatus(200);
        $response->assertSee('Edit');
        $response->assertSee('Delete');
    }

    public function test_accessibility_aria_labels_present()
    {
        $response = $this->actingAs($this->user)
            ->get('/contacts/create');

        $response->assertStatus(200);
        $response->assertSee('aria-label', false);
    }

    public function test_user_feedback_component_present()
    {
        $response = $this->actingAs($this->user)
            ->get('/contacts');

        $response->assertStatus(200);
        $response->assertSee('Provide feedback');
    }
}