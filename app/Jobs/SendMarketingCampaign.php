<?php

namespace App\Jobs;

use App\Jobs\Concerns\TenantAware;
use App\Services\MailChimpService;
use App\Services\TwilioService;
use App\Services\WhatsAppBusinessService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMarketingCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TenantAware;

    public function __construct(protected \App\Models\MarketingCampaign $campaign)
    {
        // Campaign + recipients are tenant-scoped; remember the dispatching team
        // so the recipient update below can't leak across teams in the worker.
        $this->captureTenant();
    }

    public function handle(MailChimpService $mailchimp, TwilioService $twilio, WhatsAppBusinessService $whatsapp): void
    {
        try {
            switch ($this->campaign->type) {
                case 'email':
                    $this->sendEmailCampaign($mailchimp);
                    break;
                case 'sms':
                    $this->sendSMSCampaign($twilio);
                    break;
                case 'whatsapp':
                    $this->sendWhatsAppCampaign($whatsapp);
                    break;
            }

            // Mark this campaign's recipients as sent (tenant-scoped bulk update).
            $this->campaign->recipients()->update(['status' => 'sent']);
            $this->campaign->update(['status' => 'sent']);
        } catch (Exception $e) {
            Log::error('Failed to send marketing campaign: '.$e->getMessage());
            $this->campaign->update(['status' => 'failed']);
        }
    }

    protected function sendEmailCampaign(MailChimpService $mailchimp)
    {
        // Implementation for sending email campaign
    }

    protected function sendSMSCampaign(TwilioService $twilio)
    {
        // Implementation for sending SMS campaign
    }

    protected function sendWhatsAppCampaign(WhatsAppBusinessService $whatsapp)
    {
        // Implementation for sending WhatsApp campaign
    }
}
