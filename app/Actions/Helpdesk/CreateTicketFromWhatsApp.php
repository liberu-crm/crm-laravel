<?php

namespace App\Actions\Helpdesk;

use App\Models\Ticket;
use App\Models\User;

class CreateTicketFromWhatsApp
{
    public function execute(array $message): Ticket
    {
        $from = $message['from'] ?? $message['phone_number'] ?? '';
        $content = $message['message'] ?? $message['content'] ?? '';
        $messageId = $message['id'] ?? null;

        // Synthesize a unique email for WhatsApp contacts who don't have one
        $syntheticEmail = 'whatsapp+' . preg_replace('/[^a-zA-Z0-9]/', '', $from) . '@whatsapp.invalid';

        $user = User::firstOrCreate(
            ['email' => $syntheticEmail],
            ['name' => $from]
        );

        return Ticket::create([
            'subject' => 'WhatsApp message from ' . $from,
            'body' => $content,
            'status' => 'open',
            'priority' => 'medium',
            'user_id' => $user->id,
            'email_id' => $messageId,
            'source' => 'whatsapp',
            'source_id' => $from,
        ]);
    }
}
