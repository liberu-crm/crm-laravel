<?php

namespace App\Services;

use App\Models\OAuthConfiguration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ImapService
{
    protected $connection;

    public function getUnreadMessages(OAuthConfiguration $config): Collection
    {
        try {
            $this->connect($config);
            
            $mailbox = '{' . $config->additional_settings['host'] . ':' . ($config->additional_settings['port'] ?? 993) . '/imap/ssl}INBOX';
            $emails = imap_search($this->connection, 'UNSEEN');
            
            $messages = collect();
            if ($emails) {
                foreach ($emails as $emailNumber) {
                    $message = $this->parseMessage($emailNumber);
                    if ($message) {
                        $messages->push($message);
                    }
                }
            }
            
            $this->disconnect();
            return $messages;
        } catch (\Exception $e) {
            Log::error('Error fetching IMAP messages: ' . $e->getMessage());
            $this->disconnect();
            throw $e;
        }
    }

    public function getMessage($messageId, OAuthConfiguration $config)
    {
        try {
            $this->connect($config);
            
            $message = $this->parseMessage($messageId);
            
            $this->disconnect();
            return $message;
        } catch (\Exception $e) {
            Log::error('Error fetching IMAP message: ' . $e->getMessage());
            $this->disconnect();
            throw $e;
        }
    }

    public function sendReply($messageId, $content, OAuthConfiguration $config)
    {
        try {
            $this->connect($config);
            
            // Get original message to get recipient
            $header = imap_headerinfo($this->connection, $messageId);
            $to = $header->from[0]->mailbox . '@' . $header->from[0]->host;
            $subject = 'Re: ' . ($header->subject ?? '');
            
            // Send reply via SMTP (IMAP doesn't support sending)
            $this->sendViaSmtp($to, $subject, $content, $config);
            
            $this->disconnect();
            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Error sending IMAP reply: ' . $e->getMessage());
            $this->disconnect();
            throw $e;
        }
    }

    public function sendMessage($to, $subject, $content, OAuthConfiguration $config)
    {
        try {
            $this->sendViaSmtp($to, $subject, $content, $config);
            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Error sending IMAP message: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function connect(OAuthConfiguration $config)
    {
        $host = $config->additional_settings['host'] ?? '';
        $port = $config->additional_settings['port'] ?? 993;
        $username = $config->additional_settings['username'] ?? $config->client_id;
        $password = $config->additional_settings['password'] ?? $config->client_secret;
        $ssl = $config->additional_settings['ssl'] ?? true;
        
        $mailbox = '{' . $host . ':' . $port . '/imap' . ($ssl ? '/ssl' : '') . '}INBOX';
        
        $this->connection = imap_open($mailbox, $username, $password);
        
        if (!$this->connection) {
            throw new \Exception('Failed to connect to IMAP server: ' . imap_last_error());
        }
    }

    protected function disconnect()
    {
        if ($this->connection) {
            imap_close($this->connection);
            $this->connection = null;
        }
    }

    protected function parseMessage($emailNumber)
    {
        $header = imap_headerinfo($this->connection, $emailNumber);
        $structure = imap_fetchstructure($this->connection, $emailNumber);
        
        $body = $this->getMessageBody($emailNumber, $structure);
        
        $from = $header->from[0]->mailbox . '@' . $header->from[0]->host;
        
        // Get CC recipients
        $cc = [];
        if (isset($header->cc)) {
            foreach ($header->cc as $recipient) {
                $cc[] = $recipient->mailbox . '@' . $recipient->host;
            }
        }
        
        // Get BCC recipients (usually not available via IMAP)
        $bcc = [];
        if (isset($header->bcc)) {
            foreach ($header->bcc as $recipient) {
                $bcc[] = $recipient->mailbox . '@' . $recipient->host;
            }
        }
        
        return [
            'id' => $emailNumber,
            'from' => $from,
            'subject' => $header->subject ?? '',
            'message' => $body,
            'content' => $body,
            'timestamp' => isset($header->date) ? date('Y-m-d H:i:s', strtotime($header->date)) : now(),
            'thread_id' => $header->message_id ?? null,
            'attachments' => $this->getAttachments($emailNumber, $structure),
            'status' => 'received',
            'cc' => $cc,
            'bcc' => $bcc,
        ];
    }

    protected function getMessageBody($emailNumber, $structure)
    {
        $body = '';
        
        if (!isset($structure->parts)) {
            // Simple message
            $body = imap_body($this->connection, $emailNumber);
            
            if ($structure->encoding == 3) { // BASE64
                $body = base64_decode($body);
            } elseif ($structure->encoding == 4) { // QUOTED-PRINTABLE
                $body = quoted_printable_decode($body);
            }
        } else {
            // Multipart message
            foreach ($structure->parts as $partNumber => $part) {
                if ($part->type == 0) { // Text
                    $body = imap_fetchbody($this->connection, $emailNumber, $partNumber + 1);
                    
                    if ($part->encoding == 3) { // BASE64
                        $body = base64_decode($body);
                    } elseif ($part->encoding == 4) { // QUOTED-PRINTABLE
                        $body = quoted_printable_decode($body);
                    }
                    
                    break;
                }
            }
        }
        
        return $body;
    }

    protected function getAttachments($emailNumber, $structure)
    {
        $attachments = [];
        
        if (isset($structure->parts)) {
            foreach ($structure->parts as $partNumber => $part) {
                if (isset($part->disposition) && strtolower($part->disposition) == 'attachment') {
                    $attachments[] = [
                        'filename' => $part->dparameters[0]->value ?? 'unknown',
                        'size' => $part->bytes ?? 0,
                    ];
                }
            }
        }
        
        return $attachments;
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
            Log::error('Error sending email via SMTP: ' . $e->getMessage());
            throw $e;
        }
    }
}
