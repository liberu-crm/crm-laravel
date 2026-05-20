<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageReceived
{
    use Dispatchable, SerializesModels;

    public array $message;

    public function __construct(array $message)
    {
        $this->message = $message;
    }
}
