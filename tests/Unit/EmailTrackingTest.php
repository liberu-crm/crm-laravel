<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\GmailService;
use App\Services\MailChimpService;
use App\Models\Email;
use Mockery;
use Google_Service_Gmail_Message;
use Google_Service_Gmail_MessagePart;
use Google_Service_Gmail_MessagePartHeader;
use Illuminate\Support\Facades\Log;

class EmailTrackingTest extends TestCase
{
    protected $gmailService;
    protected $mailChimpService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gmailService = Mockery::mock(GmailService::class)->makePartial();
        $this->mailChimpService = Mockery::mock(MailChimpService::class)->makePartial();
    }

    public function testTrackEmail()
    {
        $message = $this->createMockMessage();

        $this->gmailService->trackEmail($message);

        $this->assertDatabaseHas('emails', [
            'message_id' => '12345',
            'sender' => 'sender@example.com',
            'recipient' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'is_sent' => false,
        ]);
    }

    public function testTrackSentEmail()
    {
        $message = $this->createMockMessage();

        $this->gmailService->trackEmail($message, true);

        $this->assertDatabaseHas('emails', [
            'message_id' => '12345',
            'sender' => 'sender@example.com',
            'recipient' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'is_sent' => true,
        ]);
    }

    public function testTrackEmailOpen()
    {
        Log::shouldReceive('info')
            ->once()
            ->with("Email opened: Campaign ID campaign_123, Email ID email_456");

        $this->mailChimpService->trackEmailOpen('campaign_123', 'email_456');
    }

    public function testTrackEmailClick()
    {
        Log::shouldReceive('info')
            ->once()
            ->with("Email link clicked: Campaign ID campaign_123, Email ID email_456, URL: https://example.com");

        $this->mailChimpService->trackEmailClick('campaign_123', 'email_456', 'https://example.com');
    }

    public function testGetCampaignReport()
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

        $report = $this->mailChimpService->getCampaignReport('campaign_123');

        $this->assertEquals($mockReport, $report);
    }

    protected function createMockMessage()
    {
        $message = Mockery::mock(Google_Service_Gmail_Message::class);
        $message->shouldReceive('getId')->andReturn('12345');

        $payload = Mockery::mock(Google_Service_Gmail_MessagePart::class);
        $message->shouldReceive('getPayload')->andReturn($payload);

        $headers = [
            $this->createMockHeader('From', 'sender@example.com'),
            $this->createMockHeader('To', 'recipient@example.com'),
            $this->createMockHeader('Subject', 'Test Subject'),
            $this->createMockHeader('Date', '2023-06-01T12:00:00Z'),
        ];

        $payload->shouldReceive('getHeaders')->andReturn($headers);
        $payload->shouldReceive('getBody->getData')->andReturn(base64_encode('Test email content'));


        return $message;
    }

    protected function createMockHeader($name, $value)
    {
        $header = Mockery::mock(Google_Service_Gmail_MessagePartHeader::class);
        $header->shouldReceive('getName')->andReturn($name);
        $header->shouldReceive('getValue')->andReturn($value);
        return $header;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}