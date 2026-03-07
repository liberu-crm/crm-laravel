<?php

namespace Tests\Feature;

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContactSearchPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Contact::factory()->count(50)->create();
    }

    public function testContactQueryCanHandleMultipleRecords()
    {
        $start = microtime(true);
        $result = Contact::all();
        $elapsed = microtime(true) - $start;

        $this->assertCount(50, $result);
        $this->assertLessThan(5.0, $elapsed, 'Contact query took more than 5 seconds');
    }

    public function testContactSearchByName()
    {
        $contact = Contact::factory()->create(['name' => 'UniqueSearchName12345']);

        $results = Contact::where('name', 'like', '%UniqueSearchName%')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($contact->id, $results->first()->id);
    }

    public function testContactSearchByEmail()
    {
        $contact = Contact::factory()->create(['email' => 'uniquesearch12345@example.com']);

        $results = Contact::where('email', 'like', '%uniquesearch12345%')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($contact->id, $results->first()->id);
    }

    public function testContactFilterByStatus()
    {
        $activeBefore = Contact::where('status', 'active')->count();
        $inactiveBefore = Contact::where('status', 'inactive')->count();

        Contact::factory()->count(10)->create(['status' => 'active']);
        Contact::factory()->count(5)->create(['status' => 'inactive']);

        $activeCount = Contact::where('status', 'active')->count();
        $inactiveCount = Contact::where('status', 'inactive')->count();

        $this->assertEquals($activeBefore + 10, $activeCount);
        $this->assertEquals($inactiveBefore + 5, $inactiveCount);
    }
}
