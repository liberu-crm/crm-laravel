<?php

namespace App\Services;

use Facebook\Facebook;
use Illuminate\Support\Facades\Http;

class FacebookMessengerService
{
    protected $facebook;
    protected $pageId;
    protected $pageAccessToken;

    public function __construct()
    {
        $this->facebook = new Facebook([
            'app_id' => config('services.facebook.app_id'),
            'app_secret' => config('services.facebook.app_secret'),
            'default_graph_version' => 'v12.0',
        ]);
        $this->pageId = config('services.facebook.page_id');
        $this->pageAccessToken = config('services.facebook.page_access_token');
    }

    public function getUnreadMessages()
    {
        $response = $this->facebook->get("/{$this->pageId}/conversations?fields=messages{message,from,created_time}&unread=true", $this->pageAccessToken);
        $conversations = $response->getGraphEdge();

        $unreadMessages = [];
        foreach ($conversations as $conversation) {
            $messages = $conversation->getField('messages');
            foreach ($messages as $message) {
                $unreadMessages[] = [
                    'id' => $message->getField('id'),
                    'from' => $message->getField('from')['name'],
                    'message' => $message->getField('message'),
                    'created_time' => $message->getField('created_time'),
                ];
            }
        }

        return $unreadMessages;
    }

    public function getMessage($messageId)
    {
        $response = $this->facebook->get("/{$messageId}?fields=message,from,created_time", $this->pageAccessToken);
        $message = $response->getGraphNode();

        return [
            'id' => $message->getField('id'),
            'from' => $message->getField('from')['name'],
            'message' => $message->getField('message'),
            'created_time' => $message->getField('created_time'),
        ];
    }

    public function sendReply($recipientId, $message)
    {
        $response = $this->facebook->post("/{$this->pageId}/messages", [
            'recipient' => ['id' => $recipientId],
            'message' => ['text' => $message],
        ], $this->pageAccessToken);

        return $response->getGraphNode();
    }
}