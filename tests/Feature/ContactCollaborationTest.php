<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Contact;
use App\Models\Team;
use App\Http\Livewire\ContactCollaboration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class ContactCollaborationTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_can_be_updated_via_livewire()
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $this->actingAs($user);

        Livewire::test(ContactCollaboration::class, ['contact' => $contact])
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('status', 'active')
            ->call('updateContact');

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_contact_search_works_in_livewire()
    {
        $user = User::factory()->create();
        $contact1 = Contact::factory()->create(['name' => 'Alice Smith']);
        $contact2 = Contact::factory()->create(['name' => 'Bob Jones']);

        $this->actingAs($user);

        Livewire::test(ContactCollaboration::class)
            ->set('search', 'Alice')
            ->assertSee('Alice Smith')
            ->assertDontSee('Bob Jones');
    }

    public function test_contact_status_filter_works()
    {
        $user = User::factory()->create();
        $activeContact = Contact::factory()->create(['name' => 'Active User', 'status' => 'active']);
        $inactiveContact = Contact::factory()->create(['name' => 'Inactive User', 'status' => 'inactive']);

        $this->actingAs($user);

        Livewire::test(ContactCollaboration::class)
            ->set('statusFilter', 'active')
            ->assertSee('Active User')
            ->assertDontSee('Inactive User');
    }
}
