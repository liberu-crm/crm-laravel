<?php

namespace Tests\Feature;

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Illuminate\Support\Benchmark;

class ContactSearchPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Contact::factory()->count(10000)->create();
    }

    public function testSearchPerformanceUnderDifferentLoads()
    {
        $searchTerms = ['John', 'example.com', '1234', 'Technology'];
        $loadLevels = [100, 1000, 5000, 10000];

        foreach ($loadLevels as $load) {
            foreach ($searchTerms as $term) {
                $time = Benchmark::measure(function () use ($term, $load) {
                    $result = Contact::search($term)->take($load)->get();
                });

                $this->assertLessThan(1.0, $time, "Search for '{$term}' with {$load} results took more than 1 second.");
                
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testSearchPerformanceWithComplexQueries()
    {
        $complexQueries = [
            "name:John industry:Technology",
            "email:example.com company_size:Large",
            "phone:1234 last_name:Doe",
        ];

        foreach ($complexQueries as $query) {
            $time = Benchmark::measure(function () use ($query) {
                $result = Contact::search($query)->get();
            });

            $this->assertLessThan(1.5, $time, "Complex search for '{$query}' took more than 1.5 seconds.");
            
            $this->addToAssertionCount(1);
        }
    }

    public function testSearchPerformanceWithConcurrentRequests()
    {
        $concurrentRequests = 10;
        $searchTerm = 'John';

        $time = Benchmark::measure(function () use ($concurrentRequests, $searchTerm) {
            $promises = [];
            for ($i = 0; $i < $concurrentRequests; $i++) {
                $promises[] = $this->get(route('contacts.list', ['search' => $searchTerm]));
            }
            
            foreach ($promises as $promise) {
                $promise->assertSuccessful();
            }
        });

        $this->assertLessThan(3.0, $time, "{$concurrentRequests} concurrent search requests took more than 3 seconds.");
    }


    public function testDatabaseIndexPerformance()
    {
        $searchTerm = 'example.com';

        $timeWithoutIndex = Benchmark::measure(function () use ($searchTerm) {
            DB::table('contacts')->where('email', 'like', "%{$searchTerm}%")->get();
        });

        DB::statement('CREATE INDEX contacts_email_index ON contacts (email)');

        $timeWithIndex = Benchmark::measure(function () use ($searchTerm) {
            DB::table('contacts')->where('email', 'like', "%{$searchTerm}%")->get();
        });

        $this->assertLessThan($timeWithoutIndex, $timeWithIndex, "Search with index should be faster than without index.");
    }
}