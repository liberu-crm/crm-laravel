<?php

namespace App\Events;


use App\Models\Contact;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $contact;

    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('contact.' . $this->contact->id);
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->contact->id,
            'name' => $this->contact->name,
            'email' => $this->contact->email,
            'phone_number' => $this->contact->phone_number,
            'status' => $this->contact->status,
        ];
    }
}