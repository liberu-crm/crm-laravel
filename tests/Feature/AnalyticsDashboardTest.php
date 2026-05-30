<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use App\Services\ReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
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

    public function test_analytics_dashboard_access()
    {
        $team = $this->user->ownedTeams->first();
        $response = $this->actingAs($this->user)
            ->get('/app/'.$team->id.'/analytics-dashboards');

        $this->assertTrue(
            in_array($response->status(), [200, 302]),
            "Expected analytics dashboard to return 200 or 302, got {$response->status()}"
        );
    }

    public function test_contact_stats_data()
    {
        Contact::factory()->count(5)->create();
        Lead::factory()->count(3)->create();

        $this->assertEquals(5, Contact::count());
        $this->assertEquals(3, Lead::count());
    }

    public function test_sales_pipeline_data_retrieval()
    {
        $dealStages = ['Prospecting', 'Qualification', 'Proposal'];
        foreach ($dealStages as $stage) {
            Deal::factory()->count(2)->create(['stage' => $stage]);
        }

        $service = app(ReportingService::class);
        $data = $service->getSalesPipelineData([]);

        $this->assertInstanceOf(Collection::class, $data);
        $this->assertGreaterThan(0, $data->count());
    }

    public function test_customer_engagement_data_retrieval()
    {
        $service = app(ReportingService::class);
        $data = $service->getContactInteractionsData([]);

        $this->assertInstanceOf(Collection::class, $data);
    }

    public function test_data_retrieval_for_charts()
    {
        Deal::factory()->count(10)->create();
        Contact::factory()->count(20)->create();

        $service = app(ReportingService::class);
        $salesPipelineData = $service->getSalesPipelineData([]);
        $customerEngagementData = $service->getContactInteractionsData([]);

        $this->assertInstanceOf(Collection::class, $salesPipelineData);
        $this->assertInstanceOf(Collection::class, $customerEngagementData);
    }
}
