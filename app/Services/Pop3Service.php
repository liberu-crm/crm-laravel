<?php

namespace App\Services;

use App\Models\OAuthConfiguration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class Pop3Service
{
    protected $connection;
    protected $config;

    public function getUnreadMessages(OAuthConfiguration $config): Collection
    {
        try {
            $this->config = $config;
            $this->connect();
            
            $messageCount = $this->getMessageCount();
            $messages = collect();
            
            // POP3 doesn't have concept of "unread", so we fetch recent messages
            $limit = min($messageCount, 10); // Fetch last 10 messages
            
            for ($i = $messageCount; $i > max(0, $messageCount - $limit); $i--) {
                $message = $this->parseMessage($i);
                if ($message) {
                    $messages->push($message);
                }
            }
            
            $this->disconnect();
            return $messages;
        } catch (\Exception $e) {
            Log::error('Error fetching POP3 messages: ' . $e->getMessage());
            $this->disconnect();
            throw $e;
        }
    }

    public function getMessage($messageId, OAuthConfiguration $config)
    {
        try {
            $this->config = $config;
            $this->connect();
            
            $message = $this->parseMessage($messageId);
            
            $this->disconnect();
            return $message;
        } catch (\Exception $e) {
            Log::error('Error fetching POP3 message: ' . $e->getMessage());
            $this->disconnect();
            throw $e;
        }
    }

    public function sendReply($messageId, $content, OAuthConfiguration $config)
    {
        try {
            $this->config = $config;
            $this->connect();
            
            // Get original message
            $message = $this->parseMessage($messageId);
            $to = $message['from'];
            $subject = 'Re: ' . ($message['subject'] ?? '');
            
            // Send via SMTP (POP3 is receive-only)
            $this->sendViaSmtp($to, $subject, $content);
            
            $this->disconnect();
            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Error sending POP3 reply: ' . $e->getMessage());
            $this->disconnect();
            throw $e;
        }
    }

    public function sendMessage($to, $subject, $content, OAuthConfiguration $config)
    {
        try {
            $this->config = $config;
            $this->sendViaSmtp($to, $subject, $content);
            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Error sending POP3 message: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function connect()
    {
        $host = $this->config->additional_settings['host'] ?? '';
        $port = $this->config->additional_settings['port'] ?? 110;
        $username = $this->config->additional_settings['username'] ?? $this->config->client_id;
        $password = $this->config->additional_settings['password'] ?? $this->config->client_secret;
        $ssl = $this->config->additional_settings['ssl'] ?? false;
        
        $connectionString = $ssl ? "ssl://{$host}" : $host;
        
        $this->connection = fsockopen($connectionString, $port, $errno, $errstr, 30);
        
        if (!$this->connection) {
            throw new \Exception("Failed to connect to POP3 server: {$errstr} ({$errno})");
        }
        
        // Read greeting
        $this->getResponse();
        
        // Login
        $this->sendCommand("USER {$username}");
        $this->sendCommand("PASS {$password}");
    }

    protected function disconnect()
    {
        if ($this->connection) {
            $this->sendCommand("QUIT");
            fclose($this->connection);
            $this->connection = null;
        }
    }

    protected function sendCommand($command)
    {
        fwrite($this->connection, $command . "\r\n");
        return $this->getResponse();
    }

    protected function getResponse()
    {
        $response = fgets($this->connection, 512);
        
        if (substr($response, 0, 3) === '-ERR') {
            throw new \Exception('POP3 Error: ' . $response);
        }
        
        return $response;
    }

    protected function getMessageCount()
    {
        $response = $this->sendCommand("STAT");
        preg_match('/\+OK (\d+)/', $response, $matches);
        return isset($matches[1]) ? (int)$matches[1] : 0;
    }

    protected function parseMessage($messageNumber)
    {
        // Retrieve message
        $this->sendCommand("RETR {$messageNumber}");
        
        $messageLines = [];
        while (true) {
            $line = fgets($this->connection, 1024);
            if (trim($line) === '.') {
                break;
            }
            $messageLines[] = $line;
        }
        
        $rawMessage = implode('', $messageLines);
        
        // Parse headers and body
        list($headers, $body) = $this->parseRawMessage($rawMessage);
        
        return [
            'id' => $messageNumber,
            'from' => $headers['From'] ?? '',
            'subject' => $headers['Subject'] ?? '',
            'message' => $body,
            'content' => $body,
            'timestamp' => isset($headers['Date']) ? date('Y-m-d H:i:s', strtotime($headers['Date'])) : now(),
            'thread_id' => $headers['Message-ID'] ?? null,
            'attachments' => [],
            'status' => 'received',
            'cc' => isset($headers['Cc']) ? explode(',', $headers['Cc']) : [],
            'bcc' => isset($headers['Bcc']) ? explode(',', $headers['Bcc']) : [],
        ];
    }

    protected function parseRawMessage($rawMessage)
    {
        $parts = explode("\r\n\r\n", $rawMessage, 2);
        $headerLines = explode("\r\n", $parts[0]);
        $body = isset($parts[1]) ? $parts[1] : '';
        
        $headers = [];
        foreach ($headerLines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        
        // Decode body if needed
        if (isset($headers['Content-Transfer-Encoding'])) {
            if (strtolower($headers['Content-Transfer-Encoding']) === 'base64') {
                $body = base64_decode($body);
            } elseif (strtolower($headers['Content-Transfer-Encoding']) === 'quoted-printable') {
                $body = quoted_printable_decode($body);
            }
        }
        
        return [$headers, $body];
    }

    protected function sendViaSmtp($to, $subject, $content)
    {
        $from = $this->config->additional_settings['from_email'] ?? $this->config->additional_settings['username'] ?? $this->config->client_id;
        
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
