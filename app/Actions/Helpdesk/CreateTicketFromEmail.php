<?php

namespace App\Actions\Helpdesk;

use App\Models\Ticket;
use App\Models\User;

class CreateTicketFromEmail
{
    public function execute($message)
    {
        // Handle both Gmail message objects and array messages from other services
        if (is_array($message)) {
            $headers = [
                'From' => $message['from'] ?? '',
                'Subject' => $message['subject'] ?? '',
            ];
            $body = $message['content'] ?? $message['message'] ?? '';
            $emailId = $message['id'] ?? null;
        } else {
            // Gmail message object
            $headers = $this->getHeaders($message);
            $body = $this->getBody($message);
            $emailId = $message->getId();
        }

        $user = User::firstOrCreate(['email' => $headers['From']]);

        return Ticket::create([
            'subject' => $headers['Subject'],
            'body' => $body,
            'status' => 'open',
            'priority' => 'medium',
            'user_id' => $user->id,
            'email_id' => $emailId,
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