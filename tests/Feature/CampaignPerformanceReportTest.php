<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\MailChimpService;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CampaignPerformanceReportTest extends TestCase
{
    use RefreshDatabase;

    protected $mailChimpService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mailChimpService = Mockery::mock(MailChimpService::class)->makePartial();
        $this->app->instance(MailChimpService::class, $this->mailChimpService);
    }

    public function testGenerateCampaignPerformanceReport()
    {
        $mockReport = [
            'campaign_id' => 'campaign_123',
            'emails_sent' => 1000,
            'unique_opens' => 500,
            'open_rate' => 0.5,
            'clicks' => 200,
            'click_rate' => 0.2,
            'unsubscribes' => 10,
            'bounce_rate' => 0.02,
        ];

        $this->mailChimpService->shouldReceive('getCampaignReport')
            ->once()
            ->with('campaign_123')
            ->andReturn($mockReport);

        $response = $this->get('/reports/campaign-performance/campaign_123');
        
        $response->assertStatus(200);
        $response->assertViewIs('reports.email-campaign-performance');
        $response->assertViewHas('data', $mockReport);
        $response->assertSee('Campaign Performance: campaign_123');
        $response->assertSee('Emails Sent: 1,000');
        $response->assertSee('Open Rate: 50.00%');
        $response->assertSee('Click Rate: 20.00%');
    }

    public function testGenerateABTestResultsReport()
    {
        $mockResults = [
            'campaign_id' => 'campaign_123',
            'subject_a' => 'Subject A',
            'subject_b' => 'Subject B',
            'opens_a' => 300,
            'opens_b' => 200,
            'clicks_a' => 150,
            'clicks_b' => 100,
            'winner' => 'a',
            'winning_metric' => 'opens',
            'winning_metric_value' => 300,
        ];

        $this->mailChimpService->shouldReceive('getABTestResults')
            ->once()
            ->with('campaign_123')
            ->andReturn($mockResults);

        $response = $this->get('/reports/ab-test-results/campaign_123');
        
        $response->assertStatus(200);
        $response->assertViewIs('reports.ab-test-results');
        $response->assertViewHas('data', $mockResults);
        $response->assertSee('A/B Test Results for Campaign: campaign_123');
        $response->assertSee('Subject A: Subject A');

        $response->assertSee('Subject B: Subject B');
        $response->assertSee('Opens A: 300');
        $response->assertSee('Opens B: 200');
        $response->assertSee('Clicks A: 150');
        $response->assertSee('Clicks B: 100');
        $response->assertSee('Winner: Version A');
        $response->assertSee('Winning Metric: Opens');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}