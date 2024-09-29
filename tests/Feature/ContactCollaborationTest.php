<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Contact;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class ContactCollaborationTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_can_be_updated_in_real_time()
    {
        $team = Team::factory()->create();

        $user1 = User::factory()->create(['team_id' => $team->id]);
        $user2 = User::factory()->create(['team_id' => $team->id]);
        $contact = Contact::factory()->create(['team_id' => $team->id]);

        $this->actingAs($user1);

        Livewire::test('contact-collaboration', ['contact' => $contact])
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->call('updateContact');

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->actingAs($user2);

        Livewire::test('contact-collaboration', ['contact' => $contact])
            ->assertSet('name', 'John Doe')
            ->assertSet('email', 'john@example.com');
    }

    public function test_unauthorized_user_cannot_update_contact()
    {
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();
        $user1 = User::factory()->create(['team_id' => $team1->id]);
        $user2 = User::factory()->create(['team_id' => $team2->id]);
        $contact = Contact::factory()->create(['team_id' => $team1->id]);

        $this->actingAs($user2);

        Livewire::test('contact-collaboration', ['contact' => $contact])
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->call('updateContact')
            ->assertForbidden();

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }
}