<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Deal;
use App\Services\ReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->user->current_team_id = $this->user->ownedTeams->first()->id;
        $this->user->save();
    }

    public function testAnalyticsDashboardAccess()
    {
        $team = $this->user->ownedTeams->first();
        $response = $this->actingAs($this->user)
            ->get('/app/' . $team->id . '/analytics-dashboards');

        $this->assertTrue(
            in_array($response->status(), [200, 302]),
            "Expected analytics dashboard to return 200 or 302, got {$response->status()}"
        );
    }

    public function testContactStatsData()
    {
        Contact::factory()->count(5)->create();
        Lead::factory()->count(3)->create();

        $this->assertEquals(5, Contact::count());
        $this->assertEquals(3, Lead::count());
    }

    public function testSalesPipelineDataRetrieval()
    {
        $dealStages = ['Prospecting', 'Qualification', 'Proposal'];
        foreach ($dealStages as $stage) {
            Deal::factory()->count(2)->create(['stage' => $stage]);
        }

        $service = app(ReportingService::class);
        $data = $service->getSalesPipelineData([]);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $data);
        $this->assertGreaterThan(0, $data->count());
    }

    public function testCustomerEngagementDataRetrieval()
    {
        $service = app(ReportingService::class);
        $data = $service->getContactInteractionsData([]);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $data);
    }

    public function testDataRetrievalForCharts()
    {
        Deal::factory()->count(10)->create();
        Contact::factory()->count(20)->create();

        $service = app(ReportingService::class);
        $salesPipelineData = $service->getSalesPipelineData([]);
        $customerEngagementData = $service->getContactInteractionsData([]);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $salesPipelineData);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $customerEngagementData);
    }
}
