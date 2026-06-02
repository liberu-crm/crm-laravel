<?php

namespace App\Services;

use Log;
use MailchimpMarketing\ApiClient;

class MailChimpService
{
    protected \MailchimpMarketing\ApiClient $client;

    public function __construct()
    {
        $this->client = new ApiClient;
        $this->client->setConfig([
            'apiKey' => config('services.mailchimp.api_key'),
            'server' => config('services.mailchimp.server_prefix'),
        ]);
    }

    public function setClient(ApiClient $client): void
    {
        $this->client = $client;
    }

    public function getLists()
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $response = $this->client->lists->getAllLists();

        return collect($response->lists)->pluck('name', 'id')->toArray();
    }

    public function isConfigured(): bool
    {
        return ! empty(config('services.mailchimp.api_key'))
            && ! empty(config('services.mailchimp.server_prefix'));
    }

    public function createList($name, $company, $permission_reminder, $from_name, $from_email)
    {
        if (! $this->isConfigured()) {
            return null;
        }

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
        if (! $this->isConfigured()) {
            return null;
        }

        return $this->client->lists->addListMember($list_id, [
            'email_address' => $email,
            'status' => $status,
            'merge_fields' => $merge_fields,
        ]);
    }

    public function createCampaign($list_id, $subject, $from_name, $reply_to, $html_content)
    {
        if (! $this->isConfigured()) {
            return null;
        }

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

    public function createABTestCampaign($list_id, $subject_a, $subject_b, $from_name, $reply_to, $html_content_a, $html_content_b, $test_size = 50, $winner_criteria = 'opens')
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $campaign = $this->client->campaigns->create([
            'type' => 'abtest',
            'recipients' => [
                'list_id' => $list_id,
            ],
            'settings' => [
                'subject_line' => $subject_a,
                'from_name' => $from_name,
                'reply_to' => $reply_to,
            ],
            'variate_settings' => [
                'winner_criteria' => $winner_criteria,
                'test_size' => $test_size,
                'wait_time' => 1,
                'subject_lines' => [$subject_a, $subject_b],
            ],
        ]);

        $this->client->campaigns->setContent($campaign['id'], [
            'html' => $html_content_a,
        ]);

        $this->client->campaigns->updateContentAB($campaign['id'], [
            'html' => $html_content_b,
        ]);

        return $campaign;
    }

    public function sendCampaign($campaign_id)
    {
        if (! $this->isConfigured()) {
            return null;
        }

        return $this->client->campaigns->send($campaign_id);
    }

    public function getCampaigns()
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $response = $this->client->campaigns->list();

        return collect($response->campaigns)->map(fn($campaign) => [
            'id' => $campaign->id,
            'web_id' => $campaign->web_id,
            'name' => $campaign->settings->title,
            'subject_line' => $campaign->settings->subject_line,
            'status' => $campaign->status,
            'type' => $campaign->type,
        ])->toArray();
    }

    public function getCampaignReport($campaign_id): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $report = $this->client->reports->getCampaignReport($campaign_id);

        return [
            'campaign_id' => $campaign_id,
            'emails_sent' => $report->emails_sent,
            'unique_opens' => $report->opens->unique_opens,
            'open_rate' => $report->opens->open_rate,
            'clicks' => $report->clicks->clicks_total,
            'click_rate' => $report->clicks->click_rate,
            'unsubscribes' => $report->unsubscribed,
            'bounce_rate' => $report->bounces->hard_bounces + $report->bounces->soft_bounces,
        ];
    }

    public function getABTestResults($campaign_id): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $this->client->reports->getCampaignReport($campaign_id);
        $abResults = $this->client->reports->getABTestReportSummary($campaign_id);

        return [
            'campaign_id' => $campaign_id,
            'subject_a' => $abResults->a->subject_line,
            'subject_b' => $abResults->b->subject_line,
            'opens_a' => $abResults->a->opens,
            'opens_b' => $abResults->b->opens,
            'clicks_a' => $abResults->a->clicks,
            'clicks_b' => $abResults->b->clicks,
            'winner' => $abResults->winning_combination_id,
            'winning_metric' => $abResults->winning_metric,
            'winning_metric_value' => $abResults->winning_metric_value,
        ];
    }

    public function trackEmailOpen($campaign_id, $email_id): bool
    {
        Log::info("Email opened: Campaign ID {$campaign_id}, Email ID {$email_id}");

        return true;
    }

    public function trackEmailClick($campaign_id, $email_id, $url): bool
    {
        Log::info("Email link clicked: Campaign ID {$campaign_id}, Email ID {$email_id}, URL: {$url}");

        return true;
    }
}
