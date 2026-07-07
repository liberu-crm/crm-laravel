<?php

namespace App\Events;

use App\Models\Team;
use App\Models\Ticket;
use Illuminate\Foundation\Events\Dispatchable;

class NewTicket
{
    use Dispatchable;

    public function __construct(public Ticket $ticket) {}

    /** Team this event belongs to — used to scope notifications (anti cross-tenant leak). */
    public function team(): ?Team
    {
        return $this->ticket->team;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
            'priority' => $this->ticket->priority,
            'status' => $this->ticket->status,
            'source' => $this->ticket->source,
            'team_id' => $this->ticket->getAttribute('team_id'),
        ];
    }
}
