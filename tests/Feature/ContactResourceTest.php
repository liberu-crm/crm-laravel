<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactResourceTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_can_view_contact_index_page()
    {
        $response = $this->get('/app/contacts');
        $response->assertStatus(200);
    }

    public function test_can_create_contact()
    {
        $contactData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone_number' => '1234567890',
        ];

        $response = $this->post('/app/contacts', $contactData);
        $response->assertRedirect('/app/contacts');

        $this->assertDatabaseHas('contacts', $contactData);
    }

    public function test_can_edit_contact()
    {
        $contact = Contact::factory()->create();
        $updatedData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone_number' => '9876543210',
        ];

        $response = $this->put("/app/contacts/{$contact->id}", $updatedData);
        $response->assertRedirect('/app/contacts');

        $this->assertDatabaseHas('contacts', $updatedData);
    }
}