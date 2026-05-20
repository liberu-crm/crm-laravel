<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReplySent
{
    use Dispatchable, SerializesModels;

    public string $messageId;
    public string $content;
    public string $channel;
    public int|string $accountId;

    public function __construct(string $messageId, string $content, string $channel, int|string $accountId)
    {
        $this->messageId = $messageId;
        $this->content = $content;
        $this->channel = $channel;
        $this->accountId = $accountId;
    }
}
