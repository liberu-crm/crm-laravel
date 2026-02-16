<?php

namespace App\Services;

use InvalidArgumentException;
use App\Models\OAuthConfiguration;
use App\Models\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use App\Events\NewMessageReceived;
use App\Events\MessageReplySent;
use Carbon\Carbon;
use Throwable;

class UnifiedHelpDeskService
{
    protected $whatsAppService;
    protected $facebookMessengerService;
    protected $gmailService;
    protected $outlookService;
    protected $imapService;
    protected $pop3Service;
    protected $cacheTimeout = 300; // 5 minutes

    public function __construct(
        WhatsAppBusinessService $whatsAppService,
        FacebookMessengerService $facebookMessengerService,
        GmailService $gmailService,
        OutlookService $outlookService,
        ImapService $imapService,
        Pop3Service $pop3Service
    ) {
        $this->whatsAppService = $whatsAppService;
        $this->facebookMessengerService = $facebookMessengerService;
        $this->gmailService = $gmailService;
        $this->outlookService = $outlookService;
        $this->imapService = $imapService;
        $this->pop3Service = $pop3Service;
    }

    public function getAllMessages($accountId = null, $useCache = true)
    {
        $cacheKey = "messages_" . ($accountId ?? 'all');

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $messages = collect();
        $errors = collect();

        try {
            $messages = $this->fetchMessagesFromAllPlatforms($accountId, $errors);
        } catch (Throwable $e) {
            Log::error('Critical error fetching unified messages: ' . $e->getMessage());
            throw $e;
        }

        if ($errors->isNotEmpty()) {
            Log::warning('Some errors occurred while fetching messages:', $errors->toArray());
        }

        $sortedMessages = $messages->sortByDesc('timestamp');
        
        if ($useCache) {
            Cache::put($cacheKey, $sortedMessages, $this->cacheTimeout);
        }

        return $sortedMessages;
    }

    protected function fetchMessagesFromAllPlatforms($accountId, &$errors)
    {
        $messages = collect();
        
        // Fetch messages from each platform in parallel using async operations
        $platforms = [
            'whatsapp' => fn($config) => $this->whatsAppService->getMessages($config),
            'facebook' => fn($config) => $this->facebookMessengerService->getUnreadMessages($config),
            'gmail' => fn($config) => $this->gmailService->getUnreadMessages($config),
            'outlook' => fn($config) => $this->outlookService->getUnreadMessages($config),
            'microsoft365' => fn($config) => $this->outlookService->getUnreadMessages($config),
            'imap' => fn($config) => $this->imapService->getUnreadMessages($config),
            'pop3' => fn($config) => $this->pop3Service->getUnreadMessages($config),
        ];

        foreach ($platforms as $platform => $fetcher) {
            try {
                $configs = $this->getActiveConfigs($platform, $accountId);
                
                foreach ($configs as $config) {
                    try {
                        $platformMessages = $fetcher($config)
                            ->map(fn($msg) => $this->formatMessage($msg, $platform, $config));
                        $messages = $messages->merge($platformMessages);
                        
                        // Dispatch event for new messages
                        foreach ($platformMessages as $message) {
                            Event::dispatch(new NewMessageReceived($message));
                        }
                    } catch (Throwable $e) {
                        $errors->push([
                            'platform' => $platform,
                            'config_id' => $config->id,
                            'error' => $e->getMessage()
                        ]);
                        Log::error("Error fetching {$platform} messages for config {$config->id}: " . $e->getMessage());
                        continue;
                    }
                }
            } catch (Throwable $e) {
                $errors->push([
                    'platform' => $platform,
                    'error' => $e->getMessage()
                ]);
                Log::error("Error processing platform {$platform}: " . $e->getMessage());
                continue;
            }
        }

        return $messages;
    }

    protected function getActiveConfigs($platform, $accountId = null)
    {
        return OAuthConfiguration::where('service_name', $platform)
            ->where('is_active', true)
            ->when($accountId, fn($query) => $query->where('id', $accountId))
            ->get();
    }

    public function sendReply($messageId, $content, $channel, $accountId)
    {
        $config = OAuthConfiguration::findOrFail($accountId);

        try {
            $result = match ($channel) {
                'whatsapp' => $this->whatsAppService->sendMessage($messageId, $content, $config),
                'facebook' => $this->facebookMessengerService->sendReply($messageId, $content, $config),
                'gmail' => $this->gmailService->sendReply($messageId, $content, $config),
                'outlook', 'microsoft365' => $this->outlookService->sendReply($messageId, $content, $config),
                'imap' => $this->imapService->sendReply($messageId, $content, $config),
                'pop3' => $this->pop3Service->sendReply($messageId, $content, $config),
                default => throw new InvalidArgumentException("Unsupported channel: {$channel}")
            };

            // Clear cache after sending reply
            Cache::forget("messages_" . $accountId);
            Cache::forget("messages_all");

            // Dispatch event for sent reply
            Event::dispatch(new MessageReplySent($messageId, $content, $channel, $accountId));

            return $result;
        } catch (Throwable $e) {
            Log::error("Failed to send reply on {$channel}: " . $e->getMessage());
            throw $e;
        }
    }

    protected function formatMessage($message, $channel, $config)
    {
        $formatted = [
            'id' => $message['id'],
            'channel' => $channel,
            'account_id' => $config->id,
            'account_name' => $config->account_name,
            'from' => $message['from'],
            'content' => $message['message'] ?? $message['content'],
            'timestamp' => $this->normalizeTimestamp($message['timestamp'] ?? $message['created_time']),
            'thread_id' => $message['thread_id'] ?? null,
            'attachments' => $message['attachments'] ?? [],
            'status' => $message['status'] ?? 'received',
            'priority' => $this->calculatePriority($message),
            'metadata' => [
                'service_specific_data' => $message,
                'config_id' => $config->id,
                'platform_specific' => $this->getPlatformSpecificData($message, $channel)
            ]
        ];

        return $formatted;
    }

    protected function normalizeTimestamp($timestamp)
    {
        if (is_numeric($timestamp)) {
            return Carbon::createFromTimestamp($timestamp);
        }
        return Carbon::parse($timestamp);
    }

    protected function calculatePriority($message)
    {
        // Implement priority calculation logic based on keywords, sender, etc.
        $priority = 'normal';
        $urgentKeywords = ['urgent', 'asap', 'emergency', 'critical'];
        
        if (isset($message['message']) && is_string($message['message'])) {
            $content = strtolower($message['message']);
            foreach ($urgentKeywords as $keyword) {
                if (str_contains($content, $keyword)) {
                    $priority = 'high';
                    break;
                }
            }
        }
        
        return $priority;
    }

    protected function getPlatformSpecificData($message, $channel)
    {
        switch ($channel) {
            case 'whatsapp':
                return [
                    'message_type' => $message['type'] ?? 'text',
                    'phone_number' => $message['phone_number'] ?? null,
                ];
            case 'facebook':
                return [
                    'page_id' => $message['page_id'] ?? null,
                    'sender_id' => $message['sender_id'] ?? null,
                ];
            case 'gmail':
            case 'outlook':
            case 'microsoft365':
            case 'imap':
            case 'pop3':
                return [
                    'subject' => $message['subject'] ?? null,
                    'cc' => $message['cc'] ?? [],
                    'bcc' => $message['bcc'] ?? [],
                ];
            default:
                return [];
        }
    }
}