<?php

namespace Tests\Feature\Api;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    public function test_can_list_contacts()
    {
        Contact::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/contacts');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_can_create_contact()
    {
        $contactData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
        ];

        $response = $this->postJson('/api/v1/contacts', $contactData);

        $response->assertStatus(201)
            ->assertJsonFragment($contactData);
    }

    public function test_can_show_contact()
    {
        $contact = Contact::factory()->create();

        $response = $this->getJson("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(200)
            ->assertJson($contact->toArray());
    }

    public function test_can_update_contact()
    {
        $contact = Contact::factory()->create();
        $updatedData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ];


        $response = $this->putJson("/api/v1/contacts/{$contact->id}", $updatedData);

        $response->assertStatus(200)
            ->assertJsonFragment($updatedData);
    }

    public function test_can_delete_contact()
    {
        $contact = Contact::factory()->create();

        $response = $this->deleteJson("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }
}