<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ListContactsTest extends TestCase
{
    use RefreshDatabase;

    public function test_created_at_filter(): void
    {
        Contact::factory()->count(5)->create(['created_at' => now()->subDays(10)]);
        Contact::factory()->count(3)->create(['created_at' => now()->subDays(5)]);

        $response = $this->get(route('contacts.list', ['created_at' => now()->subDays(7)]));
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->count() === 3);
    }

    public function test_bulk_delete_action(): void
    {
        $user = User::factory()->create();
        $contacts = Contact::factory()->count(5)->create();
        $deleteIds = $contacts->pluck('id')->toArray();

        $this->actingAs($user)->delete(route('contacts.bulk.delete', ['ids' => $deleteIds]));
        $this->assertDatabaseMissing('contacts', ['id' => $deleteIds]);
    }

    public function test_global_search_functionality(): void
    {
        Contact::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        Contact::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

        $response = $this->get(route('contacts.list', ['search' => 'John']));
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->count() === 1 && $contacts->first()->name === 'John Doe');

        $response = $this->get(route('contacts.list', ['search' => 'john@example.com']));
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->count() === 1 && $contacts->first()->name === 'John Doe');

        $response = $this->get(route('contacts.list', ['search' => 'Nonexistent']));
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->isEmpty());
    }

    public function test_enhanced_search_functionality(): void
    {
        Contact::factory()->create([
            'name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone_number' => '1234567890',
            'company_size' => 'Small',
            'industry' => 'Technology',
        ]);
        Contact::factory()->create([
            'name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'phone_number' => '9876543210',
            'company_size' => 'Large',
            'industry' => 'Finance',
        ]);

        // phone ('1234') is encrypted at rest and no longer searchable.
        $searchTerms = ['John', 'Smith', 'john@example.com', 'Small', 'Finance'];

        foreach ($searchTerms as $term) {
            $response = $this->get(route('contacts.list', ['search' => $term]));
            $response->assertSuccessful();
            $response->assertViewHas('contacts', fn ($contacts) => $contacts->contains(fn ($contact) => Str::contains($contact->name, $term) ||
                Str::contains($contact->last_name, $term) ||
                Str::contains($contact->email, $term) ||
                Str::contains($contact->phone_number, $term) ||
                Str::contains($contact->company_size, $term) ||
                Str::contains($contact->industry, $term)));
        }
    }

    public function test_autocomplete_feature(): void
    {
        Contact::factory()->count(10)->create();
        Contact::factory()->create(['name' => 'John', 'email' => 'john.autocomplete@example.com']);

        $response = $this->get(route('contacts.autocomplete', ['query' => 'jo']));
        $response->assertSuccessful();
        $response->assertJsonStructure([
            '*' => ['id', 'name', 'email'],
        ]);

        $data = $response->json();
        $this->assertNotEmpty($data);
        foreach ($data as $item) {
            $this->assertTrue(Str::startsWith(strtolower((string) $item['name']), 'jo') || Str::contains(strtolower((string) $item['email']), 'jo'));
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
    }
}
