<?php

namespace App\Services;

use Google_Client;
use Google_Service_Gmail;

class GmailService
{
    protected $client;
    protected $service;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName(config('services.gmail.application_name'));
        $this->client->setScopes(Google_Service_Gmail::GMAIL_MODIFY);
        $this->client->setAuthConfig(config('services.gmail.credentials_path'));
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');

        $this->service = new Google_Service_Gmail($this->client);
    }

    public function getUnreadMessages()
    {
        $user = 'me';
        $optParams = [
            'q' => 'is:unread',
            'maxResults' => 10,
        ];

        $messages = $this->service->users_messages->listUsersMessages($user, $optParams);

        return $messages->getMessages();
    }

    public function getMessage($messageId)
    {
        $user = 'me';
        return $this->service->users_messages->get($user, $messageId);
    }

    public function sendReply($messageId, $body)
    {
        // Implement reply logic here
    }
}