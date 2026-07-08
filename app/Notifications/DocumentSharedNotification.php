<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentSharedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Document $document) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('A document was shared with you')
            ->line('A new document is available in your customer portal: '.$this->document->getAttribute('name'))
            // Fixed portal path (panel path 'portal', documents slug); avoids
            // panel-context URL generation inside a queued notification.
            ->action('View documents', url('/portal/documents'))
            ->line('Log in to your customer portal to download it.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'document_id' => $this->document->getKey(),
            'name' => $this->document->getAttribute('name'),
            'message' => 'A document was shared with you',
        ];
    }
}
