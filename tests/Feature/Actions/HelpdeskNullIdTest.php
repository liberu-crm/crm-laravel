<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\Helpdesk\CreateTicketFromEmail;
use App\Actions\Helpdesk\CreateTicketFromWhatsApp;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpdeskNullIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_ticket_from_an_email_with_no_id(): void
    {
        $ticket = app(CreateTicketFromEmail::class)->execute([
            'from' => 'noid@example.com',
            'subject' => 'No id here',
            'content' => 'This inbound email carries no id.',
        ], 'gmail');

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertNull($ticket->email_id);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'email_id' => null,
        ]);
    }

    public function test_it_creates_a_ticket_from_a_whatsapp_message_with_no_id(): void
    {
        $ticket = app(CreateTicketFromWhatsApp::class)->execute([
            'from' => '5550001111',
            'message' => 'This inbound WhatsApp message carries no id.',
        ]);

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertNull($ticket->email_id);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'email_id' => null,
        ]);
    }

    public function test_two_id_less_tickets_can_coexist(): void
    {
        // The unique index on email_id must tolerate multiple NULLs.
        $first = app(CreateTicketFromEmail::class)->execute([
            'from' => 'a@example.com',
            'subject' => 'first',
            'content' => 'first',
        ], 'gmail');

        $second = app(CreateTicketFromWhatsApp::class)->execute([
            'from' => '5550002222',
            'message' => 'second',
        ]);

        $this->assertNull($first->email_id);
        $this->assertNull($second->email_id);
        $this->assertCount(2, Ticket::whereNull('email_id')->get());
    }
}
