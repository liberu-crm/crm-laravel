<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\Helpdesk\CreateTicketFromEmail;
use App\Actions\Helpdesk\CreateTicketFromWhatsApp;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpdeskActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_ticket_from_an_incoming_email(): void
    {
        $ticket = app(CreateTicketFromEmail::class)->execute([
            'from' => 'jane@example.com',
            'subject' => 'Cannot log in',
            'content' => 'I keep getting an error on login.',
            'id' => 'email-abc-123',
        ], 'gmail');

        $this->assertInstanceOf(Ticket::class, $ticket);

        // A user is provisioned for the sender and linked to the ticket.
        $sender = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertSame($sender->id, $ticket->user_id);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'subject' => 'Cannot log in',
            'body' => 'I keep getting an error on login.',
            'status' => 'open',
            'priority' => 'medium',
            'source' => 'gmail',
            'source_id' => 'email-abc-123',
            'email_id' => 'email-abc-123',
            'user_id' => $sender->id,
        ]);
    }

    public function test_it_creates_a_ticket_from_an_incoming_whatsapp_message(): void
    {
        $ticket = app(CreateTicketFromWhatsApp::class)->execute([
            'from' => '1234567890',
            'message' => 'Hello, I need help',
            'id' => 'wa-msg-1',
        ]);

        $this->assertInstanceOf(Ticket::class, $ticket);

        // WhatsApp senders get a synthetic user keyed off their number.
        $sender = User::where('email', 'whatsapp+1234567890@whatsapp.invalid')->firstOrFail();
        $this->assertSame('1234567890', $sender->name);
        $this->assertSame($sender->id, $ticket->user_id);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'subject' => 'WhatsApp message from 1234567890',
            'body' => 'Hello, I need help',
            'status' => 'open',
            'priority' => 'medium',
            'source' => 'whatsapp',
            'source_id' => '1234567890',
            'email_id' => 'wa-msg-1',
            'user_id' => $sender->id,
        ]);
    }
}
