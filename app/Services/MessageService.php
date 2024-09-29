<?php

namespace App\Services;

use Google_Client;
use Google_Service_Gmail;

class MessageService
{
    protected $gmailClient;
    protected $gmailService;
    protected $whatsappService;

    public function __construct(WhatsAppBusinessService $whatsappService)
    {
        $this->gmailClient = new Google_Client();
        $this->gmailClient->setApplicationName(config('services.gmail.application_name'));
        $this->gmailClient->setScopes(Google_Service_Gmail::GMAIL_MODIFY);
        $this->gmailClient->setAuthConfig(config('services.gmail.credentials_path'));
        $this->gmailClient->setAccessType('offline');
        $this->gmailClient->setPrompt('select_account consent');

        $this->gmailService = new Google_Service_Gmail($this->gmailClient);
        $this->whatsappService = $whatsappService;
    }

    public function getUnreadMessages()
    {
        $emailMessages = $this->getUnreadEmailMessages();
        $whatsappMessages = $this->whatsappService->getUnreadMessages();

        return [
            'email' => $emailMessages,
            'whatsapp' => $whatsappMessages,
        ];
    }

    protected function getUnreadEmailMessages()
    {
        $user = 'me';
        $optParams = [
            'q' => 'is:unread',
            'maxResults' => 10,
        ];

        $messages = $this->gmailService->users_messages->listUsersMessages($user, $optParams);

        return $messages->getMessages();
    }

    public function getMessage($messageId, $type = 'email')
    {
        if ($type === 'email') {
            $user = 'me';
            return $this->gmailService->users_messages->get($user, $messageId);
        } elseif ($type === 'whatsapp') {
            return $this->whatsappService->getMessage($messageId);
        }

        throw new \InvalidArgumentException("Invalid message type: {$type}");
    }


    public function sendReply($messageId, $body, $type = 'email')
    {
        if ($type === 'email') {
            // Implement email reply logic here
        } elseif ($type === 'whatsapp') {
            return $this->whatsappService->sendReply($messageId, $body);
        }

        throw new \InvalidArgumentException("Invalid message type: {$type}");
    }
}