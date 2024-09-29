<?php

namespace App\Actions\Helpdesk;

use App\Models\Ticket;
use App\Models\User;

class CreateTicketFromEmail
{
    public function execute($message)
    {
        $headers = $this->getHeaders($message);
        $body = $this->getBody($message);

        $user = User::firstOrCreate(['email' => $headers['From']]);

        return Ticket::create([
            'subject' => $headers['Subject'],
            'body' => $body,
            'status' => 'open',
            'priority' => 'medium',
            'user_id' => $user->id,
            'email_id' => $message->getId(),
        ]);
    }

    private function getHeaders($message)
    {
        $headers = [];
        foreach ($message->getPayload()->getHeaders() as $header) {
            $headers[$header->getName()] = $header->getValue();
        }
        return $headers;
    }

    private function getBody($message)
    {
        $parts = $message->getPayload()->getParts();
        $body = '';

        if (empty($parts)) {
            $body = $message->getPayload()->getBody()->getData();
        } else {
            foreach ($parts as $part) {
                if ($part['mimeType'] === 'text/plain') {
                    $body = $part['body']['data'];
                    break;
                }
            }
        }

        return base64_decode(strtr($body, '-_', '+/'));
    }
}