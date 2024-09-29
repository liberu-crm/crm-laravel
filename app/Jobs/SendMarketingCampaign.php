<?php

namespace App\Jobs;

use App\Models\MarketingCampaign;
use App\Services\MailChimpService;
use App\Services\TwilioService;
use App\Services\WhatsAppBusinessService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMarketingCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaign;

    public function __construct(MarketingCampaign $campaign)
    {
        $this->campaign = $campaign;
    }

    public function handle(MailChimpService $mailchimp, TwilioService $twilio, WhatsAppBusinessService $whatsapp)
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

            $this->campaign->update(['status' => 'sent']);
        } catch (\Exception $e) {
            Log::error('Failed to send marketing campaign: ' . $e->getMessage());
            $this->campaign->update(['status' => 'failed']);
        }
    }

    protected function sendEmailCampaign(MailChimpService $mailchimp)
    {
        $list_id = $this->createOrGetMailChimpList($mailchimp);
        $this->addRecipientsToList($mailchimp, $list_id);
        $campaign = $this->createMailChimpCampaign($mailchimp, $list_id);
        $mailchimp->sendCampaign($campaign['id']);

        $this->campaign->recipients()->update(['status' => 'sent']);
    }

    protected function createOrGetMailChimpList(MailChimpService $mailchimp)
    {
        // Logic to create or get an existing list
        // For simplicity, we'll create a new list each time
        $list = $mailchimp->createList(
            $this->campaign->name,
            config('app.name'),
            'You are receiving this email as part of our marketing campaign.',
            config('mail.from.name'),
            config('mail.from.address')
        );
        return $list['id'];
    }

    protected function addRecipientsToList(MailChimpService $mailchimp, $list_id)
    {
        foreach ($this->campaign->recipients as $recipient) {
            $mailchimp->addMember($list_id, $recipient->email);
        }
    }

    protected function createMailChimpCampaign(MailChimpService $mailchimp, $list_id)
    {
        return $mailchimp->createCampaign(
            $list_id,
            $this->campaign->subject,
            config('mail.from.name'),
            config('mail.from.address'),
            $this->campaign->content
        );
    }

    protected function sendSMSCampaign(TwilioService $twilio)
    {
        $recipients = $this->campaign->recipients()->whereNotNull('phone')->get();
        $results = $twilio->sendBulkSMS(
            $recipients->pluck('phone')->toArray(),
            $this->campaign->content
        );

        foreach ($recipients as $index => $recipient) {
            $status = $results[$index]->status === 'queued' ? 'sent' : 'failed';
            $recipient->update(['status' => $status]);
        }
    }

    protected function sendWhatsAppCampaign(WhatsAppBusinessService $whatsapp)
    {
        $recipients = $this->campaign->recipients()->whereNotNull('phone')->get();

        foreach ($recipients as $recipient) {
            $response = $whatsapp->sendMessage($recipient->phone, $this->campaign->content);

            $status = $response['messages'][0]['status'] === 'sent' ? 'sent' : 'failed';
            $recipient->update(['status' => $status]);
        }
    }
}