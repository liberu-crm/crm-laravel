<?php

namespace Tests\Unit;

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContactStatsTest extends TestCase
{
    use RefreshDatabase;

    public function testTotalContactsCalculation()
    {
        Contact::factory()->count(10)->create();

        $totalContacts = DB::table('contacts')->count();

        $this->assertEquals(10, $totalContacts);
    }

    public function testRecentContactsCalculation()
    {
        Contact::factory()->count(5)->create(['created_at' => now()->subDays(10)]);
        Contact::factory()->count(3)->create(['created_at' => now()->subDays(60)]);

        $recentContacts = DB::table('contacts')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $this->assertEquals(5, $recentContacts);
    }

    public function testCategorizationOfContacts()
    {
        Contact::factory()->count(3)->create(['status' => 'lead']);
        Contact::factory()->count(2)->create(['status' => 'customer']);

        $categorizations = DB::table('contacts')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        $this->assertCount(2, $categorizations);
        $totalByStatus = $categorizations->pluck('total', 'status');
        $this->assertEquals(3, $totalByStatus['lead']);
        $this->assertEquals(2, $totalByStatus['customer']);
    }
}
