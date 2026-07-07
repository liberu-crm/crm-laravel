<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PortalInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $url) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You have been invited to the customer portal')
            ->line('An account has been created for you on the customer portal.')
            ->action('Set your password', $this->url)
            ->line('If you did not expect this invitation, you can ignore this email.');
    }
}
