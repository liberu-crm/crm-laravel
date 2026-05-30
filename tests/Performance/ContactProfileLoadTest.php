<?php

namespace Tests\Performance;

use App\Models\Activity;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContactProfileLoadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
    }

    /** @test */
    public function it_loads_contact_profile_within_500ms()
    {
        // Create a contact with related data
        $contact = Contact::factory()->create();
        $contact->notes()->createMany(
            Note::factory()->count(5)->make()->toArray()
        );
        $contact->deals()->createMany(
            Deal::factory()->count(3)->make()->toArray()
        );
        $contact->activities()->createMany(
            Activity::factory()->count(10)->make()->toArray()
        );

        // Warm up the cache
        $this->get(route('filament.app.resources.contacts.view', ['record' => $contact->id]));

        // Measure the time it takes to load the contact profile
        $start = microtime(true);
        $response = $this->get(route('filament.app.resources.contacts.view', ['record' => $contact->id]));
        $end = microtime(true);

        $loadTime = ($end - $start) * 1000; // Convert to milliseconds

        // Assert the response and load time
        $response->assertStatus(200);
        $this->assertLessThan(500, $loadTime, "Contact profile took {$loadTime}ms to load, which is more than 500ms");

        // Log the number of database queries
        $queryCount = count(DB::getQueryLog());
        $this->addToAssertionCount(1);
        echo "Number of database queries: {$queryCount}\n";
    }
}
