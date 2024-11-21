

<?php

namespace App\Services;

use App\Models\OAuthConfiguration;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class UnifiedHelpDeskService
{
    protected $whatsAppService;
    protected $facebookMessengerService;
    protected $gmailService;
    protected $outlookService;

    public function __construct(
        WhatsAppBusinessService $whatsAppService,
        FacebookMessengerService $facebookMessengerService,
        GmailService $gmailService,
        OutlookService $outlookService
    ) {
        $this->whatsAppService = $whatsAppService;
        $this->facebookMessengerService = $facebookMessengerService;
        $this->gmailService = $gmailService;
        $this->outlookService = $outlookService;
    }

    public function getAllMessages($accountId = null)
    {
        $messages = collect();

        try {
            // Get messages from all configured WhatsApp accounts
            $whatsAppConfigs = OAuthConfiguration::where('service_name', 'whatsapp')
                ->where('is_active', true)
                ->when($accountId, fn($query) => $query->where('id', $accountId))
                ->get();
            
            foreach ($whatsAppConfigs as $config) {
                $messages = $messages->merge(
                    $this->whatsAppService->getMessages($config)
                        ->map(fn($msg) => $this->formatMessage($msg, 'whatsapp', $config))
                );
            }

            // Get messages from all configured Facebook Messenger accounts
            $facebookConfigs = OAuthConfiguration::where('service_name', 'facebook')
                ->where('is_active', true)
                ->when($accountId, fn($query) => $query->where('id', $accountId))
                ->get();
            
            foreach ($facebookConfigs as $config) {
                $messages = $messages->merge(
                    $this->facebookMessengerService->getUnreadMessages($config)
                        ->map(fn($msg) => $this->formatMessage($msg, 'facebook', $config))
                );
            }

            // Get messages from all configured email accounts
            $emailConfigs = OAuthConfiguration::whereIn('service_name', ['gmail', 'outlook'])
                ->where('is_active', true)
                ->when($accountId, fn($query) => $query->where('id', $accountId))
                ->get();
            
            foreach ($emailConfigs as $config) {
                $service = $config->service_name === 'gmail' ? $this->gmailService : $this->outlookService;
                $messages = $messages->merge(
                    $service->getUnreadMessages($config)
                        ->map(fn($msg) => $this->formatMessage($msg, $config->service_name, $config))
                );
            }

        } catch (\Exception $e) {
            Log::error('Error fetching unified messages: ' . $e->getMessage());
            throw $e;
        }

        return $messages->sortByDesc('timestamp');
    }

    public function sendReply($messageId, $content, $channel, $accountId)
    {
        $config = OAuthConfiguration::findOrFail($accountId);

        switch ($channel) {
            case 'whatsapp':
                return $this->whatsAppService->sendMessage($content, $messageId, $config);
            
            case 'facebook':
                return $this->facebookMessengerService->sendReply($messageId, $content, $config);
            
            case 'gmail':
                return $this->gmailService->sendReply($messageId, $content, $config);
            
            case 'outlook':
                return $this->outlookService->sendReply($messageId, $content, $config);
            
            default:
                throw new \InvalidArgumentException("Unsupported channel: {$channel}");
        }
    }

    protected function formatMessage($message, $channel, $config)
    {
        return [
            'id' => $message['id'],
            'channel' => $channel,
            'account_id' => $config->id,
            'account_name' => $config->account_name,
            'from' => $message['from'],
            'content' => $message['message'] ?? $message['content'],
            'timestamp' => $message['timestamp'] ?? $message['created_time'],
            'thread_id' => $message['thread_id'] ?? null,
            'attachments' => $message['attachments'] ?? [],
            'metadata' => [
                'service_specific_data' => $message,
                'config_id' => $config->id
            ]
        ];
    }
}