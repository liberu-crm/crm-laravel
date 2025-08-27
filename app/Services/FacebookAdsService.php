<?php

namespace App\Services;

use Exception;
use App\Models\AdvertisingAccount;
use FacebookAds\Api;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\Page;
use FacebookAds\Object\Fields\PageFields;
use FacebookAds\Exception\FacebookException;
use Illuminate\Support\Facades\Log;

class FacebookAdsService
{
    protected $api;
    protected $account;

    public function __construct(AdvertisingAccount $account)
    {
        $this->account = $account;
        $this->api = $this->initializeApi();
    }

    protected function initializeApi()
    {
        Api::init(
            config('services.facebook.app_id'),
            config('services.facebook.app_secret'),
            $this->account->access_token
        );

        return Api::instance();
    }

    public function getCampaigns()
    {
        try {
            $adAccount = new AdAccount('act_' . $this->account->account_id);
            $campaigns = $adAccount->getCampaigns(['id', 'name', 'status']);

            $campaignData = [];
            foreach ($campaigns as $campaign) {
                $campaignData[] = [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                ];
            }

            return $campaignData;
        } catch (FacebookException $e) {
            Log::error('Facebook API Error: ' . $e->getMessage());
            throw new Exception('Failed to fetch campaigns: ' . $e->getMessage());
        }
    }

    public function createAndSchedulePost($pageId, $postData)
    {
        try {
            $page = new Page($pageId);

            $params = [
                'message' => $postData['message'],
                'scheduled_publish_time' => $postData['scheduled_time'],
                'published' => false,
            ];

            if (isset($postData['link'])) {
                $params['link'] = $postData['link'];
            }

            if (isset($postData['image'])) {
                $params['source'] = $postData['image'];
            }

            $result = $page->createFeed($params);

            return [
                'id' => $result->id,
                'post_id' => $result->post_id,
            ];
        } catch (FacebookException $e) {
            Log::error('Facebook API Error: ' . $e->getMessage());
            throw new Exception('Failed to create and schedule post: ' . $e->getMessage());
        }
    }

    // Add more methods for other Facebook Ads operations as needed
}