<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Contact;
use App\Http\Livewire\ContactCollaboration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;

class ContactManagementUITest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
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
            ->assertSeeInOrder(['Alice', 'Bob', 'Charlie']);
    }

    public function test_contact_index_loads_for_authenticated_user()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->current_team_id = $user->ownedTeams->first()->id;
        $user->save();

        $response = $this->actingAs($user)->get('/app/contacts');
        $response->assertSuccessful();
    }
}
