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

    public function testGetCampaignPerformanceReport()
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

        $result = app(MailChimpService::class)->getCampaignReport('campaign_123');

        $this->assertEquals('campaign_123', $result['campaign_id']);
        $this->assertEquals(1000, $result['emails_sent']);
        $this->assertEquals(0.5, $result['open_rate']);
    }

    public function testGetABTestResultsReport()
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

        $result = app(MailChimpService::class)->getABTestResults('campaign_123');

        $this->assertEquals('campaign_123', $result['campaign_id']);
        $this->assertEquals('Subject A', $result['subject_a']);
        $this->assertEquals('a', $result['winner']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
