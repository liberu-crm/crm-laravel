<?php

namespace App\Services;

use MailchimpMarketing\ApiClient;

class MailChimpService
{
    protected $client;

    public function __construct()
    {
        $this->client = new ApiClient();
        $this->client->setConfig([
            'apiKey' => config('services.mailchimp.api_key'),
            'server' => config('services.mailchimp.server_prefix')
        ]);
    }

    public function createList($name, $company, $permission_reminder, $from_name, $from_email)
    {
        return $this->client->lists->createList([
            'name' => $name,
            'permission_reminder' => $permission_reminder,
            'email_type_option' => true,
            'contact' => [
                'company' => $company,
                'address1' => '',
                'city' => '',
                'state' => '',
                'zip' => '',
                'country' => '',
            ],
            'campaign_defaults' => [
                'from_name' => $from_name,
                'from_email' => $from_email,
                'subject' => '',
                'language' => 'en',
            ],
        ]);
    }

    public function addMember($list_id, $email, $status = 'subscribed', $merge_fields = [])
    {
        return $this->client->lists->addListMember($list_id, [
            'email_address' => $email,
            'status' => $status,
            'merge_fields' => $merge_fields,
        ]);
    }

    public function createCampaign($list_id, $subject, $from_name, $reply_to, $html_content)
    {
        $campaign = $this->client->campaigns->create([
            'type' => 'regular',
            'recipients' => [
                'list_id' => $list_id,
            ],
            'settings' => [
                'subject_line' => $subject,
                'from_name' => $from_name,
                'reply_to' => $reply_to,
            ],
        ]);

        $this->client->campaigns->setContent($campaign['id'], [
            'html' => $html_content,
        ]);

        return $campaign;
    }

    public function sendCampaign($campaign_id)
    {
        return $this->client->campaigns->send($campaign_id);
    }
}