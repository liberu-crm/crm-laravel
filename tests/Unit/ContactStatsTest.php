<?php

namespace Tests\Unit;

use App\Filament\Admin\Resources\ContactResource\Widgets\ContactStats;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Illuminate\Support\Facades\View;

class ContactStatsTest extends TestCase
{
    use RefreshDatabase;

    public function testTotalContactsCalculation()
    {
        DB::shouldReceive('table->count')->andReturn(10);
        View::shouldReceive('make->with')->andReturnUsing(function ($view, $data) {
            $this->assertEquals(10, $data['totalContacts']);
        });

        (new ContactStats())->render();
    }

    public function testRecentContactsCalculation()
    {
        DB::shouldReceive('table->where->count')->andReturn(5);
        View::shouldReceive('make->with')->andReturnUsing(function ($view, $data) {
            $this->assertEquals(5, $data['recentContacts']);
        });

        (new ContactStats())->render();
    }

    public function testCategorizationOfContacts()
    {
        $expectedCategorizations = collect([
            (object)['category' => 'Friend', 'total' => 3],
            (object)['category' => 'Family', 'total' => 2],
        ]);

        DB::shouldReceive('table->select->groupBy->get')->andReturn($expectedCategorizations);
        View::shouldReceive('make->with')->andReturnUsing(function ($view, $data) use ($expectedCategorizations) {
            $this->assertEquals($expectedCategorizations, $data['categorizations']);
        });

        (new ContactStats())->render();
    }
}
