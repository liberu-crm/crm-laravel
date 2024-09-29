<?php

namespace Tests\Unit;

use App\Services\WhatsAppBusinessService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppBusinessServiceTest extends TestCase
{
    protected $whatsAppService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->whatsAppService = new WhatsAppBusinessService();
    }

    public function testGetUnreadMessages()
    {
        Http::fake([
            '*' => Http::response([
                'messages' => [
                    ['id' => '1', 'body' => 'Test message 1'],
                    ['id' => '2', 'body' => 'Test message 2'],
                ]
            ], 200),
        ]);

        $messages = $this->whatsAppService->getUnreadMessages();

        $this->assertCount(2, $messages);
        $this->assertEquals('Test message 1', $messages[0]['body']);
        $this->assertEquals('Test message 2', $messages[1]['body']);
    }

    public function testGetMessage()
    {
        Http::fake([
            '*' => Http::response([
                'id' => '1',
                'body' => 'Test message',
                'from' => '1234567890',
            ], 200),
        ]);

        $message = $this->whatsAppService->getMessage('1');

        $this->assertEquals('1', $message['id']);
        $this->assertEquals('Test message', $message['body']);
        $this->assertEquals('1234567890', $message['from']);
    }

    public function testSendReply()
    {
        Http::fake([
            '*' => Http::response([
                'id' => '1',
                'status' => 'sent',
            ], 200),
        ]);

        $response = $this->whatsAppService->sendReply('1234567890', 'Test reply');

        $this->assertEquals('1', $response['id']);
        $this->assertEquals('sent', $response['status']);
    }
}