<?php

namespace App\Jobs;

use App\Services\OutlookService;
use App\Actions\Helpdesk\CreateTicketFromEmail;
use App\Models\OAuthConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchOutlookTickets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $configId;

    public function __construct($configId = null)
    {
        $this->configId = $configId;
    }

    public function handle(OutlookService $outlookService, CreateTicketFromEmail $createTicket)
    {
        try {
            $configs = $this->configId 
                ? [OAuthConfiguration::findOrFail($this->configId)]
                : OAuthConfiguration::where('service_name', 'outlook')
                    ->orWhere('service_name', 'microsoft365')
                    ->where('is_active', true)
                    ->get();

            foreach ($configs as $config) {
                try {
                    $messages = $outlookService->getUnreadMessages($config);

                    foreach ($messages as $message) {
                        $createTicket->execute($message);
                    }
                } catch (\Exception $e) {
                    Log::error("Error fetching Outlook tickets for config {$config->id}: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in FetchOutlookTickets job: ' . $e->getMessage());
        }
    }
}
