<?php

declare(strict_types=1);

namespace App\Actions\Portal;

use App\Models\Message;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketReplyNotification;

/**
 * Persists a staff reply to a portal-origin ticket as a Message on the thread
 * (mirroring the customer reply idiom in the portal ViewTicket) and notifies the
 * requester. Portal tickets have no external channel, so they never route through
 * UnifiedHelpDeskService; the customer reads the reply in their portal thread.
 */
class ReplyToPortalTicket
{
    public function __invoke(Ticket $ticket, string $content, User $staff): Message
    {
        $message = new Message([
            'channel' => 'portal',
            // Display name, not email — the customer sees the thread, so no
            // internal staff email is exposed.
            'sender' => $staff->getAttribute('name'),
            'content' => $content,
            'priority' => $ticket->getAttribute('priority') ?: 'medium',
            'status' => 'unread',
            // ponytail: messages.account_id is a legacy NOT NULL int with no
            // default; 0 sentinel when the ticket has no source account.
            'account_id' => (int) ($ticket->getAttribute('account_id') ?? 0),
            'metadata' => [],
            'timestamp' => now(),
            'ticket_id' => $ticket->getKey(),
        ]);

        // team_id is system-managed (not fillable on Message). Set it directly so
        // the reply carries the ticket's tenant and stays visible to staff on the
        // team-scoped app panel; the portal is non-tenant, so IsTenantModel's
        // creating hook won't stamp it.
        $message->setAttribute('team_id', $ticket->getAttribute('team_id'));
        $message->save();

        $ticket->user()->first()?->notify(new TicketReplyNotification($ticket));

        return $message;
    }
}
