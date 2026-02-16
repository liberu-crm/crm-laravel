<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\OAuthConfiguration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class WhatsAppBusinessService
{
    protected $apiUrl;
    protected $accessToken;

    public function __construct()
    {
        // Initialize with default config values
        $this->apiUrl = config('services.whatsapp.api_url');
        $this->accessToken = config('services.whatsapp.access_token');
    }

    protected function initializeFromConfig(?OAuthConfiguration $config = null)
    {
        if ($config) {
            $this->apiUrl = $config->additional_settings['api_url'] ?? $this->apiUrl;
            $this->accessToken = $config->additional_settings['access_token'] ?? $config->client_secret;
        }
    }

    public function getUnreadMessages(?OAuthConfiguration $config = null): Collection
    {
        try {
            $this->initializeFromConfig($config);
            
            $response = Http::withToken($this->accessToken)
                ->get($this->apiUrl . '/messages', [
                    'status' => 'unread',
                    'limit' => 10,
                ]);

            $messages = $response->json()['messages'] ?? [];
            
            return collect($messages)->map(function ($msg) {
                return [
                    'id' => $msg['id'] ?? '',
                    'from' => $msg['from'] ?? '',
                    'message' => $msg['text']['body'] ?? $msg['message'] ?? '',
                    'content' => $msg['text']['body'] ?? $msg['message'] ?? '',
                    'timestamp' => $msg['timestamp'] ?? time(),
                    'thread_id' => $msg['conversation_id'] ?? null,
                    'attachments' => $msg['attachments'] ?? [],
                    'status' => 'received',
                    'type' => $msg['type'] ?? 'text',
                    'phone_number' => $msg['from'] ?? '',
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error fetching WhatsApp messages: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getMessages(?OAuthConfiguration $config = null): Collection
    {
        return $this->getUnreadMessages($config);
    }

    public function getMessage($messageId, ?OAuthConfiguration $config = null)
    {
        $this->initializeFromConfig($config);
        
        $response = Http::withToken($this->accessToken)
            ->get($this->apiUrl . '/messages/' . $messageId);

        return $response->json();
    }

    public function sendReply($to, $body, ?OAuthConfiguration $config = null)
    {
        $this->initializeFromConfig($config);
        
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

    public function sendMessage($to, $body, ?OAuthConfiguration $config = null)
    {
        $this->initializeFromConfig($config);
        
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