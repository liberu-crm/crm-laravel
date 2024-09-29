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

    public function testWhatsAppMessageCreatesTicket()
    {
        Queue::fake();

        $user = User::factory()->create();

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

        $messageService->shouldReceive('getMessage')
            ->with('whatsapp_123', 'whatsapp')
            ->andReturn([
                'id' => 'whatsapp_123',
                'from' => '1234567890',
                'body' => 'Test WhatsApp message',
            ]);

        FetchMessages::dispatch();

        Queue::assertPushed(FetchMessages::class);

        $this->assertDatabaseHas('tickets', [
            'subject' => 'WhatsApp message from 1234567890',
            'content' => 'Test WhatsApp message',
            'source' => 'whatsapp',
            'source_id' => '1234567890',
        ]);

        $ticket = Ticket::where('source', 'whatsapp')->first();
        $this->assertNotNull($ticket);
        $this->assertEquals('Test WhatsApp message', $ticket->content);
    }
}