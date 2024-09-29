<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\GmailService;
use App\Models\Email;
use Mockery;
use Google_Service_Gmail_Message;
use Google_Service_Gmail_MessagePart;
use Google_Service_Gmail_MessagePartHeader;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmailTrackingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $gmailService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gmailService = Mockery::mock(GmailService::class)->makePartial();
        $this->app->instance(GmailService::class, $this->gmailService);
    }

    public function testEmailTrackingIntegration()
    {
        $message = $this->createMockMessage();

        $this->gmailService->shouldReceive('getMessage')->andReturn($message);
        $this->gmailService->shouldReceive('sendReply')->andReturn($message);

        // Test receiving a message
        $response = $this->get('/messages/12345?type=email');
        $response->assertStatus(200);

        $this->assertDatabaseHas('emails', [
            'message_id' => '12345',
            'sender' => 'sender@example.com',
            'recipient' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'is_sent' => false,
        ]);

        // Test sending a reply
        $response = $this->post('/messages/12345/reply', [
            'body' => 'This is a test reply',
            'type' => 'email',
        ]);
        $response->assertStatus(200);

        $this->assertDatabaseHas('emails', [
            'message_id' => '12345',
            'sender' => 'sender@example.com',
            'recipient' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'is_sent' => true,
        ]);

        // Test displaying tracked emails in the helpdesk view
        $response = $this->get('/helpdesk');
        $response->assertStatus(200);
        $response->assertSee('Test Subject');
        $response->assertSee('sender@example.com');
        $response->assertSee('recipient@example.com');
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