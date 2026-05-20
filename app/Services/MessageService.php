<?php

namespace App\Services;

use InvalidArgumentException;
use Google_Service_Gmail_Message;
use Google_Client;
use Google_Service_Gmail;
use App\Models\Email;
use App\Models\ConnectedAccount;

class MessageService
{
    protected $gmailClient;
    protected $gmailService;
    protected $whatsappService;
    protected $facebookMessengerService;

    public function __construct(WhatsAppBusinessService $whatsappService, FacebookMessengerService $facebookMessengerService)
    {
        $this->gmailClient = new Google_Client();
        $this->gmailClient->setApplicationName(config('services.gmail.application_name'));
        $this->gmailClient->setScopes(Google_Service_Gmail::GMAIL_MODIFY);
        $this->gmailClient->setAuthConfig(config('services.gmail.credentials_path'));
        $this->gmailClient->setAccessType('offline');

        $this->gmailClient->setPrompt('select_account consent');

        $this->gmailService = new Google_Service_Gmail($this->gmailClient);
        $this->whatsappService = $whatsappService;
        $this->facebookMessengerService = $facebookMessengerService;
    }

    public function getUnreadMessages()
    {
        $messages = [
            'email' => [],
            'whatsapp' => [],
            'facebook' => [],
        ];

        $emailAccounts = ConnectedAccount::ofType('gmail')->get();
        foreach ($emailAccounts as $account) {
            $messages['email'] = array_merge($messages['email'], $this->getUnreadEmailMessages($account));
        }

        $whatsappAccounts = ConnectedAccount::ofType('whatsapp')->get();
        foreach ($whatsappAccounts as $account) {
            $messages['whatsapp'] = array_merge($messages['whatsapp'], $this->whatsappService->getUnreadMessages($account));
        }

        $facebookAccounts = ConnectedAccount::ofType('facebook')->get();
        foreach ($facebookAccounts as $account) {
            $messages['facebook'] = array_merge($messages['facebook'], $this->facebookMessengerService->getUnreadMessages($account));
        }

        return $messages;
    }

    protected function getUnreadEmailMessages(ConnectedAccount $account)
    {
        $this->gmailClient->setAccessToken($account->token);
        $user = 'me';
        $optParams = [
            'q' => 'is:unread',
            'maxResults' => 10,
        ];

        $messages = $this->gmailService->users_messages->listUsersMessages($user, $optParams);

        return array_map(function($message) use ($account) {
            $message->accountId = $account->id;
            return $message;
        }, $messages->getMessages());
    }

    public function getMessage($messageId, $type = 'email', $accountId = null)
    {
        $account = ConnectedAccount::findOrFail($accountId);

        switch ($type) {
            case 'email':
                $this->gmailClient->setAccessToken($account->token);
                $user = 'me';
                $message = $this->gmailService->users_messages->get($user, $messageId);
                $this->trackEmail($message, $account);
                return $message;
            case 'whatsapp':
                return $this->whatsappService->getMessage($account, $messageId);
            case 'facebook':
                return $this->facebookMessengerService->getMessage($account, $messageId);
            default:
                throw new InvalidArgumentException("Invalid message type: {$type}");
        }
    }

    public function sendReply($messageId, $body, $type = 'email', $accountId = null)
    {
        $account = ConnectedAccount::findOrFail($accountId);

        switch ($type) {
            case 'email':
                $this->gmailClient->setAccessToken($account->token);
                $user = 'me';
                $reply = $this->createReplyMessage($messageId, $body);
                $sentMessage = $this->gmailService->users_messages->send($user, $reply);
                $this->trackEmail($sentMessage, $account, true);
                return $sentMessage;
            case 'whatsapp':
                return $this->whatsappService->sendReply($account, $messageId, $body);
            case 'facebook':
                return $this->facebookMessengerService->sendReply($account, $messageId, $body);
            default:
                throw new InvalidArgumentException("Invalid message type: {$type}");
        }
    }

    protected function trackEmail($message, ConnectedAccount $account, $isSent = false)
    {
        $headers = $this->parseHeaders($message->getPayload()->getHeaders());

        Email::create([
            'message_id' => $message->getId(),
            'sender' => $headers['From'] ?? '',
            'recipient' => $headers['To'] ?? '',
            'subject' => $headers['Subject'] ?? '',
            'content' => $this->getEmailContent($message),
            'timestamp' => $headers['Date'] ?? now(),
            'is_sent' => $isSent,
            'connected_account_id' => $account->id,
        ]);
    }

    protected function parseHeaders($headers)
    {
        $parsedHeaders = [];
        foreach ($headers as $header) {
            $parsedHeaders[$header->getName()] = $header->getValue();
        }
        return $parsedHeaders;
    }

    protected function getEmailContent($message)
    {
        $payload = $message->getPayload();
        if (!$payload) {
            return '';
        }

        $parts = $payload->getParts();
        $body = $payload->getBody();

        if ($body && $body->getData()) {
            return base64_decode(strtr($body->getData(), '-_', '+/'));
        }

        if ($parts) {
            foreach ($parts as $part) {
                if ($part['mimeType'] === 'text/plain') {
                    $data = $part['body']['data'];
                    return base64_decode(strtr($data, '-_', '+/'));
                }
            }
        }

        return '';
    }

    protected function createReplyMessage($originalMessageId, $replyBody)
    {
        $originalMessage = $this->gmailService->users_messages->get('me', $originalMessageId);
        $headers = $this->parseHeaders($originalMessage->getPayload()->getHeaders());

        $replyMessage = new Google_Service_Gmail_Message();
        $rawMessageString = "From: me\r\n";
        $rawMessageString .= "To: {$headers['From']}\r\n";
        $rawMessageString .= 'Subject: Re: ' . ($headers['Subject'] ?? '') . "\r\n";
        $rawMessageString .= "Content-Type: text/plain; charset=utf-8\r\n";
        $rawMessageString .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $rawMessageString .= base64_encode($replyBody);

        $replyMessage->setRaw(base64_encode($rawMessageString));
        $replyMessage->setThreadId($originalMessage->getThreadId());

        return $replyMessage;
    }
}