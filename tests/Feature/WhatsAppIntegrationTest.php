<?php

namespace Tests\Feature;

use App\Jobs\FetchMessages;
use App\Models\Ticket;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function testFetchMessagesJobCanBeQueued()
    {
        Queue::fake();

        FetchMessages::dispatch();

        Queue::assertPushed(FetchMessages::class);
    }

    public function testMessageServiceGetUnreadMessages()
    {
        $messageService = $this->mock(MessageService::class);
        $messageService->shouldReceive('getUnreadMessages')->andReturn([
            'email' => [],
            'whatsapp' => [
                [
                    'id' => 'whatsapp_123',
                    'from' => '1234567890',
                    'body' => 'Test WhatsApp message',
                ]
            ]
        ]);

        $result = app(MessageService::class)->getUnreadMessages();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('whatsapp', $result);
        $this->assertCount(1, $result['whatsapp']);
        $this->assertEquals('Test WhatsApp message', $result['whatsapp'][0]['body']);
    }

    public function testTicketCanBeCreatedForWhatsAppMessage()
    {
        $ticket = Ticket::factory()->create([
            'subject' => 'WhatsApp message from 1234567890',
            'source' => 'whatsapp',
        ]);

        $this->assertDatabaseHas('tickets', [
            'subject' => 'WhatsApp message from 1234567890',
            'source' => 'whatsapp',
        ]);
    }
}
