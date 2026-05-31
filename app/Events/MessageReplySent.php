<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReplySent
{
    use Dispatchable, SerializesModels;

    public function __construct(public string $messageId, public string $content, public string $channel, public int|string $accountId)
    {
    }
}
