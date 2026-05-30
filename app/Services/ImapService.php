<?php

namespace App\Services;

use App\Models\OAuthConfiguration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;

class ImapService
{
    public function getUnreadMessages(OAuthConfiguration $config): Collection
    {
        try {
            $client = $this->connect($config);
            $folder = $client->getFolder('INBOX');
            $messages = $folder->messages()->whereUnseen()->get();

            $parsed = collect();
            foreach ($messages as $message) {
                $parsed->push($this->parseMessage($message));
            }

            return $parsed;
        } catch (\Exception $e) {
            Log::error('Error fetching IMAP messages: '.$e->getMessage());
            throw $e;
        }
    }

    public function getMessage($messageId, OAuthConfiguration $config)
    {
        try {
            $client = $this->connect($config);
            $folder = $client->getFolder('INBOX');
            $message = $folder->messages()->getMessageByUid($messageId);

            if (! $message) {
                return null;
            }

            return $this->parseMessage($message);
        } catch (\Exception $e) {
            Log::error('Error fetching IMAP message: '.$e->getMessage());
            throw $e;
        }
    }

    public function sendReply($messageId, $content, OAuthConfiguration $config)
    {
        try {
            $client = $this->connect($config);
            $folder = $client->getFolder('INBOX');
            $message = $folder->messages()->getMessageByUid($messageId);

            if (! $message) {
                throw new \Exception('Message not found: '.$messageId);
            }

            $from = $message->getFrom()->first();
            $to = $from->mail;
            $subject = 'Re: '.$message->getSubject()->toString();

            $this->sendViaSmtp($to, $subject, $content, $config);

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Error sending IMAP reply: '.$e->getMessage());
            throw $e;
        }
    }

    public function sendMessage($to, $subject, $content, OAuthConfiguration $config)
    {
        try {
            $this->sendViaSmtp($to, $subject, $content, $config);

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Error sending IMAP message: '.$e->getMessage());
            throw $e;
        }
    }

    protected function connect(OAuthConfiguration $config): Client
    {
        $host = $config->additional_settings['host'] ?? '';
        $port = (int) ($config->additional_settings['port'] ?? 993);
        $username = $config->additional_settings['username'] ?? $config->client_id;
        $password = $config->additional_settings['password'] ?? $config->client_secret;
        $ssl = $config->additional_settings['ssl'] ?? 'ssl';

        if (empty($host)) {
            throw new \Exception('IMAP host is not configured');
        }

        $encryption = match (true) {
            $ssl === true || $ssl === 'ssl' => 'ssl',
            $ssl === 'tls' => 'tls',
            default => false,
        };

        $cm = new ClientManager;
        $client = $cm->make([
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
            'encryption' => $encryption,
        ]);

        $client->connect();

        return $client;
    }

    protected function parseMessage(Message $message): array
    {
        $from = $message->getFrom()->first();

        $cc = [];
        if ($message->getCc()->count() > 0) {
            foreach ($message->getCc()->toArray() as $addr) {
                $cc[] = $addr->mail;
            }
        }

        $attachments = [];
        foreach ($message->getAttachments() as $attachment) {
            $attachments[] = [
                'filename' => $attachment->getName(),
                'size' => $attachment->getSize(),
            ];
        }

        $date = $message->getDate()?->toDate();

        return [
            'id' => $message->getUid(),
            'from' => $from ? $from->mail : '',
            'subject' => $message->getSubject()->toString() ?? '',
            'message' => $message->getTextBody() ?? $message->getHTMLBody() ?? '',
            'content' => $message->getTextBody() ?? $message->getHTMLBody() ?? '',
            'timestamp' => $date ? $date->format('Y-m-d H:i:s') : now(),
            'thread_id' => $message->getMessageId()->toString() ?? null,
            'attachments' => $attachments,
            'status' => 'received',
            'cc' => $cc,
            'bcc' => [],
        ];
    }

    protected function sendViaSmtp($to, $subject, $content, OAuthConfiguration $config)
    {
        $from = $config->additional_settings['from_email'] ?? $config->additional_settings['username'] ?? $config->client_id;

        try {
            \Mail::raw($content, function ($message) use ($to, $subject, $from) {
                $message->to($to)
                    ->subject($subject)
                    ->from($from);
            });
        } catch (\Exception $e) {
            Log::error('Error sending email via SMTP: '.$e->getMessage());
            throw $e;
        }
    }
}
