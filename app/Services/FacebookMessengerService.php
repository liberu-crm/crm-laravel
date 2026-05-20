<?php

namespace App\Services;

use App\Models\ConnectedAccount;

class FacebookMessengerService
{
    protected $facebook;

    public function __construct()
    {
        // Facebook is initialised lazily or injected via the service container
    }

    protected function getFacebook()
    {
        if ($this->facebook === null) {
            $this->facebook = app(\Facebook\Facebook::class) ?? new \Facebook\Facebook([
                'app_id'                => config('services.facebook.app_id'),
                'app_secret'            => config('services.facebook.app_secret'),
                'default_graph_version' => 'v12.0',
            ]);
        }

        return $this->facebook;
    }

    /**
     * Get unread messages for a page/account.
     *
     * When a ConnectedAccount is provided, the access token from that account is
     * used. When called without an account the default access token of the
     * Facebook instance is used (useful in tests).
     */
    public function getUnreadMessages(?ConnectedAccount $account = null): array
    {
        $fb = $this->getFacebook();

        if ($account !== null) {
            $fb->setDefaultAccessToken($account->token);
            $pageId = $account->provider_id;
        } else {
            $pageId = 'me';
        }

        $response      = $fb->get("/{$pageId}/conversations?fields=messages{message,from,created_time}&unread=true");
        $conversations = $response->getGraphEdge();

        $unreadMessages = [];
        foreach ($conversations as $conversation) {
            $messages = $conversation->messages ?? ($conversation->getField ? $conversation->getField('messages') : []);
            foreach ($messages as $message) {
                $id   = is_object($message) && method_exists($message, 'getField') ? $message->getField('id') : ($message->id ?? null);
                $from = is_object($message) && method_exists($message, 'getField') ? $message->getField('from') : ($message->from ?? []);
                $msg  = is_object($message) && method_exists($message, 'getField') ? $message->getField('message') : ($message->message ?? null);
                $time = is_object($message) && method_exists($message, 'getField') ? $message->getField('created_time') : ($message->created_time ?? null);

                $unreadMessages[] = [
                    'id'           => $id,
                    'from'         => is_array($from) ? ($from['name'] ?? null) : (is_object($from) ? $from->name ?? null : $from),
                    'message'      => $msg,
                    'created_time' => $time,
                    'account_id'   => $account?->id,
                ];
            }
        }

        return $unreadMessages;
    }

    /**
     * Fetch a single message by ID.
     */
    public function getMessage(string $messageId, ?ConnectedAccount $account = null): array
    {
        $fb = $this->getFacebook();

        if ($account !== null) {
            $fb->setDefaultAccessToken($account->token);
        }

        $response = $fb->get("/{$messageId}?fields=message,from,created_time");
        $message  = $response->getGraphNode();

        $from = is_object($message) && method_exists($message, 'getField') ? $message->getField('from') : ($message->from ?? []);

        return [
            'id'           => is_object($message) && method_exists($message, 'getField') ? $message->getField('id') : ($message->id ?? null),
            'from'         => is_array($from) ? ($from['name'] ?? null) : (is_object($from) ? $from->name ?? null : $from),
            'message'      => is_object($message) && method_exists($message, 'getField') ? $message->getField('message') : ($message->message ?? null),
            'created_time' => is_object($message) && method_exists($message, 'getField') ? $message->getField('created_time') : ($message->created_time ?? null),
            'account_id'   => $account?->id,
        ];
    }

    /**
     * Send a reply to a recipient.
     */
    public function sendReply(string $recipientId, string $message, ?ConnectedAccount $account = null)
    {
        $fb = $this->getFacebook();

        $pageId = 'me';
        if ($account !== null) {
            $fb->setDefaultAccessToken($account->token);
            $pageId = $account->provider_id;
        }

        $response = $fb->post("/{$pageId}/messages", [
            'recipient' => ['id' => $recipientId],
            'message'   => ['text' => $message],
        ]);

        return $response->getGraphNode();
    }

    public function getAllConnectedAccounts()
    {
        return ConnectedAccount::ofType('facebook')->get();
    }

    public function getPrimaryAccount(): ?ConnectedAccount
    {
        return ConnectedAccount::ofType('facebook')->primary()->first();
    }
}
