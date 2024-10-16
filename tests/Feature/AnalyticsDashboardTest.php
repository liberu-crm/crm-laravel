<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Deal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function testAnalyticsDashboardAccess()
    {
        $response = $this->actingAs($this->user)
            ->get('/analytics-dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('analytics-dashboard');
    }

    public function testAnalyticsDashboardContainsRequiredComponents()
    {
        $response = $this->actingAs($this->user)
            ->get('/analytics-dashboard');

        $response->assertStatus(200);
        $response->assertSee('Contact Stats Overview');
        $response->assertSee('Sales Pipeline Chart');
        $response->assertSee('Customer Engagement Chart');
    }

    public function testContactStatsOverview()
    {
        Contact::factory()->count(5)->create();
        Lead::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->get('/analytics-dashboard');

        $response->assertStatus(200);
        $response->assertSee('Total Contacts: 5');
        $response->assertSee('Total Leads: 3');
    }

    public function testSalesPipelineChart()
    {
        $dealStages = ['Prospecting', 'Qualification', 'Proposal', 'Negotiation', 'Closed Won', 'Closed Lost'];
        foreach ($dealStages as $stage) {
            Deal::factory()->count(rand(1, 5))->create(['stage' => $stage]);
        }

        $response = $this->actingAs($this->user)
            ->get('/analytics-dashboard');

        $response->assertStatus(200);
        foreach ($dealStages as $stage) {
            $response->assertSee($stage);
        }
    }

    public function testCustomerEngagementChart()
    {
        $engagementTypes = ['Email', 'Phone', 'Meeting', 'Social Media'];
        foreach ($engagementTypes as $type) {
            Contact::factory()->count(rand(1, 5))->create(['last_engagement_type' => $type]);
        }

        $response = $this->actingAs($this->user)
            ->get('/analytics-dashboard');

        $response->assertStatus(200);
        foreach ($engagementTypes as $type) {
            $response->assertSee($type);
        }
    }

    public function testDataRetrievalForCharts()
    {
        Deal::factory()->count(10)->create();
        Contact::factory()->count(20)->create();

        $response = $this->actingAs($this->user)
            ->get('/analytics-dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('salesPipelineData');
        $response->assertViewHas('customerEngagementData');

        $salesPipelineData = $response->viewData('salesPipelineData');
        $customerEngagementData = $response->viewData('customerEngagementData');

        $this->assertIsArray($salesPipelineData);
        $this->assertIsArray($customerEngagementData);
        $this->assertNotEmpty($salesPipelineData);
        $this->assertNotEmpty($customerEngagementData);
    }
}