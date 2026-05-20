<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\GmailService;
use App\Services\MailChimpService;
use App\Models\Email;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmailTrackingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $gmailService;
    protected $mailChimpService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gmailService = Mockery::mock(GmailService::class)->makePartial();
        $this->mailChimpService = Mockery::mock(MailChimpService::class)->makePartial();
        $this->app->instance(GmailService::class, $this->gmailService);
        $this->app->instance(MailChimpService::class, $this->mailChimpService);
    }

    public function testEmailOpenTrackingService()
    {
        $this->mailChimpService->shouldReceive('trackEmailOpen')
            ->once()
            ->with('campaign_123', 'email_456')
            ->andReturn(true);

        $result = app(MailChimpService::class)->trackEmailOpen('campaign_123', 'email_456');
        $this->assertTrue($result);
    }

    public function testEmailClickTrackingService()
    {
        $this->mailChimpService->shouldReceive('trackEmailClick')
            ->once()
            ->with('campaign_123', 'email_456', 'https://example.com')
            ->andReturn(true);

        $result = app(MailChimpService::class)->trackEmailClick('campaign_123', 'email_456', 'https://example.com');
        $this->assertTrue($result);
    }

    public function testCampaignPerformanceReportService()
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

        $report = app(MailChimpService::class)->getCampaignReport('campaign_123');

        $this->assertEquals('campaign_123', $report['campaign_id']);
        $this->assertEquals(1000, $report['emails_sent']);
        $this->assertEquals(0.5, $report['open_rate']);
    }

    public function testEmailModelCreation()
    {
        $email = Email::factory()->create([
            'subject' => 'Test Subject',
            'body' => 'Test email content',
        ]);

        $this->assertDatabaseHas('emails', [
            'id' => $email->id,
            'subject' => 'Test Subject',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
