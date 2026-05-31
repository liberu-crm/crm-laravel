<?php

namespace Tests\Feature;

use App\Jobs\FetchMessages;
use App\Models\Ticket;
use App\Services\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetch_messages_job_can_be_queued(): void
    {
        Queue::fake();

        FetchMessages::dispatch();

        Queue::assertPushed(FetchMessages::class);
    }

    public function test_message_service_get_unread_messages(): void
    {
        $messageService = $this->mock(MessageService::class);
        $messageService->shouldReceive('getUnreadMessages')->andReturn([
            'email' => [],
            'whatsapp' => [
                [
                    'id' => 'whatsapp_123',
                    'from' => '1234567890',
                    'body' => 'Test WhatsApp message',
                ],
            ],
        ]);

        $result = app(MessageService::class)->getUnreadMessages();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('whatsapp', $result);
        $this->assertCount(1, $result['whatsapp']);
        $this->assertEquals('Test WhatsApp message', $result['whatsapp'][0]['body']);
    }

    public function test_ticket_can_be_created_for_whats_app_message(): void
    {
        Ticket::factory()->create([
            'subject' => 'WhatsApp message from 1234567890',
            'source' => 'whatsapp',
        ]);

        $this->assertDatabaseHas('tickets', [
            'subject' => 'WhatsApp message from 1234567890',
            'source' => 'whatsapp',
        ]);
    }
}
