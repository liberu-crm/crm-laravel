<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MailChimpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class ReportGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function testContactInteractionsReportGeneration()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/reports/contact-interactions');

        $response->assertStatus(200);
        $response->assertViewIs('reports.contact-interactions');
        $response->assertViewHas('data');
    }

    public function testSalesPipelineReportGeneration()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/reports/sales-pipeline');

        $response->assertStatus(200);
        $response->assertViewIs('reports.sales-pipeline');
        $response->assertViewHas('data');
    }

    public function testCustomerEngagementReportGeneration()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/reports/customer-engagement');

        $response->assertStatus(200);
        $response->assertViewIs('reports.customer-engagement');
        $response->assertViewHas('data');
    }

    public function testABTestResultsReportGeneration()
    {
        $user = User::factory()->create();

        $mockMailChimpService = Mockery::mock(MailChimpService::class);
        $mockMailChimpService->shouldReceive('getABTestResults')
            ->once()
            ->with('test_campaign_id')
            ->andReturn([
                'campaign_id' => 'test_campaign_id',
                'subject_a' => 'Test Subject A',
                'subject_b' => 'Test Subject B',
                'opens_a' => 100,
                'opens_b' => 120,
                'clicks_a' => 50,
                'clicks_b' => 60,
                'winner' => 'b',
                'winning_metric' => 'opens',
                'winning_metric_value' => 120,
            ]);

        $this->app->instance(MailChimpService::class, $mockMailChimpService);

        $response = $this->actingAs($user)
            ->get('/reports/ab-test-results?campaign_id=test_campaign_id');

        $response->assertStatus(200);
        $response->assertViewIs('reports.ab-test-results');
        $response->assertViewHas('data');
        $response->assertSee('Test Subject A');
        $response->assertSee('Test Subject B');
        $response->assertSee('100');
        $response->assertSee('120');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}