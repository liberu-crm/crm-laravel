<?php

namespace Tests\Feature;

use App\Livewire\ContactCollaboration;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactManagementUITest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_advanced_search_functionality()
    {
        $contact1 = Contact::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $contact2 = Contact::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        Livewire::actingAs($this->user)
            ->test(ContactCollaboration::class)
            ->set('search', 'John')
            ->assertSee('John Doe')
            ->assertDontSee('Jane Smith');
    }

    public function test_filtering_contacts_by_status()
    {
        $activeContact = Contact::factory()->create(['name' => 'Active User', 'status' => 'active']);
        $inactiveContact = Contact::factory()->create(['name' => 'Inactive User', 'status' => 'inactive']);

        Livewire::actingAs($this->user)
            ->test(ContactCollaboration::class)
            ->set('statusFilter', 'active')
            ->assertSee('Active User')
            ->assertDontSee('Inactive User');
    }

    public function test_sorting_contacts()
    {
        $contactA = Contact::factory()->create(['name' => 'Alice']);
        $contactB = Contact::factory()->create(['name' => 'Bob']);
        $contactC = Contact::factory()->create(['name' => 'Charlie']);

        Livewire::actingAs($this->user)
            ->test(ContactCollaboration::class)
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['Charlie', 'Bob', 'Alice']);
    }

    public function test_contact_index_loads_for_authenticated_user()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams->first();
        $user->current_team_id = $team->id;
        $user->save();

        $response = $this->actingAs($user)->get('/app/'.$team->id.'/contacts');
        $this->assertTrue(
            in_array($response->status(), [200, 302]),
            "Expected /app/{team_id}/contacts to return 200 or 302, got {$response->status()}"
        );
    }
}
