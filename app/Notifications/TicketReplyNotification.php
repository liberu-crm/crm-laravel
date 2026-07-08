<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New reply on your support ticket')
            ->line('A support agent has replied to your ticket: '.$this->ticket->getAttribute('subject'))
            // Fixed portal path (panel path 'portal', tickets slug); avoids
            // panel-context URL generation inside a queued notification.
            ->action('View ticket', url('/portal/tickets/'.$this->ticket->getKey()))
            ->line('Log in to your customer portal to read and respond.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->getKey(),
            'subject' => $this->ticket->getAttribute('subject'),
            'message' => 'New reply on your ticket',
        ];
    }
}
