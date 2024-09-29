<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppBusinessService
{
    protected $apiUrl;
    protected $accessToken;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url');
        $this->accessToken = config('services.whatsapp.access_token');
    }

    public function getUnreadMessages()
    {
        $response = Http::withToken($this->accessToken)
            ->get($this->apiUrl . '/messages', [
                'status' => 'unread',
                'limit' => 10,
            ]);

        return $response->json()['messages'] ?? [];
    }

    public function getMessage($messageId)
    {
        $response = Http::withToken($this->accessToken)
            ->get($this->apiUrl . '/messages/' . $messageId);

        return $response->json();
    }

    public function sendReply($to, $body)
    {
        $response = Http::withToken($this->accessToken)
            ->post($this->apiUrl . '/messages', [
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'body' => $body
                ]
            ]);

        return $response->json();
    }

    public function sendMessage($to, $body)
    {
        $response = Http::withToken($this->accessToken)
            ->post($this->apiUrl . '/messages', [
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'body' => $body
                ]
            ]);

        return $response->json();
    }
}