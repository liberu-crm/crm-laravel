<?php

namespace App\Services;

use Facebook\Facebook;
use Illuminate\Support\Facades\Http;
use App\Models\ConnectedAccount;

class FacebookMessengerService
{
    protected $facebook;

    public function __construct()
    {
        $this->facebook = new Facebook([
            'app_id' => config('services.facebook.app_id'),
            'app_secret' => config('services.facebook.app_secret'),
            'default_graph_version' => 'v12.0',
        ]);
    }

    public function getUnreadMessages(ConnectedAccount $account)
    {
        $this->facebook->setDefaultAccessToken($account->token);
        $response = $this->facebook->get("/{$account->provider_id}/conversations?fields=messages{message,from,created_time}&unread=true");
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
                    'account_id' => $account->id,
                ];
            }
        }

        return $unreadMessages;
    }

    public function getMessage(ConnectedAccount $account, $messageId)
    {
        $this->facebook->setDefaultAccessToken($account->token);
        $response = $this->facebook->get("/{$messageId}?fields=message,from,created_time");
        $message = $response->getGraphNode();

        return [
            'id' => $message->getField('id'),
            'from' => $message->getField('from')['name'],
            'message' => $message->getField('message'),
            'created_time' => $message->getField('created_time'),
            'account_id' => $account->id,
        ];
    }

    public function sendReply(ConnectedAccount $account, $recipientId, $message)
    {
        $this->facebook->setDefaultAccessToken($account->token);
        $response = $this->facebook->post("/{$account->provider_id}/messages", [
            'recipient' => ['id' => $recipientId],
            'message' => ['text' => $message],
        ]);

        return $response->getGraphNode();
    }

    public function getAllConnectedAccounts()
    {
        return ConnectedAccount::ofType('facebook')->get();
    }

    public function getPrimaryAccount()
    {
        return ConnectedAccount::ofType('facebook')->primary()->first();
    }
}