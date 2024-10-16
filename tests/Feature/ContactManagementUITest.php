<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Contact;
use App\Models\CustomField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;

class ContactManagementUITest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ... (keep all existing tests)

    public function test_advanced_search_functionality()
    {
        $contact1 = Contact::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'active']);
        $contact2 = Contact::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com', 'status' => 'inactive']);

        Livewire::test('contact-collaboration')
            ->set('search', 'John')
            ->assertSee('John Doe')
            ->assertDontSee('Jane Smith');

        Livewire::test('contact-collaboration')
            ->set('search', 'jane@example.com')
            ->assertSee('Jane Smith')
            ->assertDontSee('John Doe');
    }

    public function test_filtering_contacts()
    {
        $activeContact = Contact::factory()->create(['name' => 'Active User', 'status' => 'active']);
        $inactiveContact = Contact::factory()->create(['name' => 'Inactive User', 'status' => 'inactive']);

        Livewire::test('contact-collaboration')
            ->set('statusFilter', 'active')
            ->assertSee('Active User')
            ->assertDontSee('Inactive User');

        Livewire::test('contact-collaboration')
            ->set('statusFilter', 'inactive')
            ->assertSee('Inactive User')
            ->assertDontSee('Active User');
    }

    public function test_sorting_contacts()
    {
        $contactA = Contact::factory()->create(['name' => 'Alice']);
        $contactB = Contact::factory()->create(['name' => 'Bob']);
        $contactC = Contact::factory()->create(['name' => 'Charlie']);

        Livewire::test('contact-collaboration')
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['Alice', 'Bob', 'Charlie']);

        Livewire::test('contact-collaboration')
            ->call('sortBy', 'name')
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['Charlie', 'Bob', 'Alice']);
    }

    // ... (keep all other existing tests)
}