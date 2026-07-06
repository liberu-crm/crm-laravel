<?php

namespace Tests\Feature\Http\Api;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactApiExtendedTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/contacts');

        $response->assertUnauthorized();
    }

    public function test_api_returns_contacts_for_team(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        Sanctum::actingAs($user);

        Contact::factory()->count(3)->create(['team_id' => $user->currentTeam->id]);

        $response = $this->getJson('/api/v1/contacts');

        $response->assertOk()
            ->assertJsonCount(3);
    }

    public function test_api_can_create_contact(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/contacts', [
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'phone_number' => '+1234567890',
        ]);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'Jane']);

        $this->assertDatabaseHas('contacts', ['email' => 'jane@example.com']);
    }

    public function test_api_contact_store_validates_required_fields(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/contacts', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_api_can_update_contact(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        Sanctum::actingAs($user);
        $contact = Contact::factory()->create(['team_id' => $user->currentTeam->id]);

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Updated Name']);
    }

    public function test_api_can_delete_contact(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        Sanctum::actingAs($user);
        $contact = Contact::factory()->create(['team_id' => $user->currentTeam->id]);

        $response = $this->deleteJson("/api/v1/contacts/{$contact->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_api_cannot_access_other_team_contact(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherUser = User::factory()->withPersonalTeam()->create();
        Sanctum::actingAs($user);

        $contact = Contact::factory()->create(['team_id' => $otherUser->currentTeam->id]);

        $response = $this->getJson("/api/v1/contacts/{$contact->id}");

        // Tenant global scope makes the record invisible, so binding 404s
        // before any policy runs. 404 (not 403) is intentional: it does not
        // disclose that another team's record exists.
        $response->assertNotFound();
    }
}
