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
        $user = User::factory()->withPersonalTeam()->create();
        $user->current_team_id = $user->ownedTeams->first()->id;
        $user->save();
        $this->actingAs($user);
    }

    public function test_can_view_contact_index_page()
    {
        $response = $this->get('/app/contacts');
        $response->assertSuccessful();
    }

    public function test_can_create_contact_model()
    {
        $contact = Contact::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone_number' => '1234567890',
        ]);

        $this->assertDatabaseHas('contacts', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone_number' => '1234567890',
        ]);
    }

    public function test_can_edit_contact_model()
    {
        $contact = Contact::factory()->create();

        $contact->update([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone_number' => '9876543210',
        ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone_number' => '9876543210',
        ]);
    }
}
