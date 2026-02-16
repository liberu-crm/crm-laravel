<?php

namespace App\Jobs;

use App\Services\Pop3Service;
use App\Actions\Helpdesk\CreateTicketFromEmail;
use App\Models\OAuthConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchPop3Tickets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $configId;

    public function __construct($configId = null)
    {
        $this->configId = $configId;
    }

    public function handle(Pop3Service $pop3Service, CreateTicketFromEmail $createTicket)
    {
        try {
            $configs = $this->configId 
                ? [OAuthConfiguration::findOrFail($this->configId)]
                : OAuthConfiguration::where('service_name', 'pop3')
                    ->where('is_active', true)
                    ->get();

            foreach ($configs as $config) {
                try {
                    $messages = $pop3Service->getUnreadMessages($config);

                    foreach ($messages as $message) {
                        $createTicket->execute($message);
                    }
                } catch (\Exception $e) {
                    Log::error("Error fetching POP3 tickets for config {$config->id}: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in FetchPop3Tickets job: ' . $e->getMessage());
        }
    }
}
