<?php

namespace App\Jobs;

use App\Services\ImapService;
use App\Actions\Helpdesk\CreateTicketFromEmail;
use App\Models\OAuthConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchImapTickets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $configId;

    public function __construct($configId = null)
    {
        $this->configId = $configId;
    }

    public function handle(ImapService $imapService, CreateTicketFromEmail $createTicket)
    {
        try {
            $configs = $this->configId 
                ? [OAuthConfiguration::findOrFail($this->configId)]
                : OAuthConfiguration::where('service_name', 'imap')
                    ->where('is_active', true)
                    ->get();

            foreach ($configs as $config) {
                try {
                    $messages = $imapService->getUnreadMessages($config);

                    foreach ($messages as $message) {
                        $createTicket->execute($message);
                    }
                } catch (\Exception $e) {
                    Log::error("Error fetching IMAP tickets for config {$config->id}: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in FetchImapTickets job: ' . $e->getMessage());
        }
    }
}
