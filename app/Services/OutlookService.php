<?php

namespace App\Services;

use App\Models\OAuthConfiguration;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\Message;
use Microsoft\Graph\Model\ItemBody;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class OutlookService
{
    protected $graph;

    public function __construct()
    {
        $this->graph = new Graph();
    }

    public function getUnreadMessages(OAuthConfiguration $config): Collection
    {
        try {
            $this->setAccessToken($config);
            
            $response = $this->graph
                ->createRequest('GET', '/me/messages')
                ->addHeaders(['Prefer' => 'outlook.body-content-type="text"'])
                ->setReturnType(Message::class)
                ->execute();

            $messages = collect();
            foreach ($response as $message) {
                if (!$message->getIsRead()) {
                    $messages->push([
                        'id' => $message->getId(),
                        'from' => $message->getFrom()->getEmailAddress()->getAddress(),
                        'subject' => $message->getSubject(),
                        'message' => $this->getMessageBody($message),
                        'content' => $this->getMessageBody($message),
                        'timestamp' => $message->getReceivedDateTime(),
                        'thread_id' => $message->getConversationId(),
                        'attachments' => $this->getAttachments($message),
                        'status' => 'received',
                        'cc' => $this->getCcRecipients($message),
                        'bcc' => $this->getBccRecipients($message),
                    ]);
                }
            }

            return $messages;
        } catch (\Exception $e) {
            Log::error('Error fetching Outlook messages: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getMessage($messageId, OAuthConfiguration $config)
    {
        try {
            $this->setAccessToken($config);
            
            $message = $this->graph
                ->createRequest('GET', "/me/messages/{$messageId}")
                ->addHeaders(['Prefer' => 'outlook.body-content-type="text"'])
                ->setReturnType(Message::class)
                ->execute();

            return [
                'id' => $message->getId(),
                'from' => $message->getFrom()->getEmailAddress()->getAddress(),
                'subject' => $message->getSubject(),
                'message' => $this->getMessageBody($message),
                'content' => $this->getMessageBody($message),
                'timestamp' => $message->getReceivedDateTime(),
                'thread_id' => $message->getConversationId(),
                'attachments' => $this->getAttachments($message),
                'cc' => $this->getCcRecipients($message),
                'bcc' => $this->getBccRecipients($message),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching Outlook message: ' . $e->getMessage());
            throw $e;
        }
    }

    public function sendReply($messageId, $content, OAuthConfiguration $config)
    {
        try {
            $this->setAccessToken($config);
            
            $replyData = [
                'message' => [
                    'body' => [
                        'contentType' => 'Text',
                        'content' => $content
                    ]
                ]
            ];

            $response = $this->graph
                ->createRequest('POST', "/me/messages/{$messageId}/reply")
                ->attachBody($replyData)
                ->execute();

            return $response;
        } catch (\Exception $e) {
            Log::error('Error sending Outlook reply: ' . $e->getMessage());
            throw $e;
        }
    }

    public function sendMessage($to, $subject, $content, OAuthConfiguration $config)
    {
        try {
            $this->setAccessToken($config);
            
            $messageData = [
                'message' => [
                    'subject' => $subject,
                    'body' => [
                        'contentType' => 'Text',
                        'content' => $content
                    ],
                    'toRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => $to
                            ]
                        ]
                    ]
                ]
            ];

            $response = $this->graph
                ->createRequest('POST', '/me/sendMail')
                ->attachBody($messageData)
                ->execute();

            return $response;
        } catch (\Exception $e) {
            Log::error('Error sending Outlook message: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function setAccessToken(OAuthConfiguration $config)
    {
        $accessToken = $config->additional_settings['access_token'] ?? null;
        
        if (!$accessToken) {
            throw new \Exception('Access token not found for Outlook configuration');
        }

        $this->graph->setAccessToken($accessToken);
    }

    protected function getMessageBody($message)
    {
        $body = $message->getBody();
        return $body ? $body->getContent() : '';
    }

    protected function getAttachments($message)
    {
        $attachments = [];
        if ($message->getHasAttachments()) {
            // Note: Fetching attachments requires additional API call
            // This is a simplified version
            $attachments = [];
        }
        return $attachments;
    }

    protected function getCcRecipients($message)
    {
        $cc = [];
        $ccRecipients = $message->getCcRecipients();
        if ($ccRecipients) {
            foreach ($ccRecipients as $recipient) {
                $cc[] = $recipient->getEmailAddress()->getAddress();
            }
        }
        return $cc;
    }

    protected function getBccRecipients($message)
    {
        $bcc = [];
        $bccRecipients = $message->getBccRecipients();
        if ($bccRecipients) {
            foreach ($bccRecipients as $recipient) {
                $bcc[] = $recipient->getEmailAddress()->getAddress();
            }
        }
        return $bcc;
    }
}
