<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CRMEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $event;
    protected $data;

    public function __construct($event, $data)
    {
        $this->event = $event;
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("CRM Event: {$this->event}")
            ->line("A new CRM event has occurred: {$this->event}")
            ->line("Details: " . $this->getEventDetails())
            ->action('View in CRM', url('/dashboard'))
            ->line('Thank you for using our application!');
    }

    public function toArray($notifiable)
    {
        return [
            'event' => $this->event,
            'data' => $this->data,
        ];
    }

    protected function getEventDetails()
    {
        // Customize this method based on the event type and data
        return json_encode($this->data);
    }
}