<?php

namespace App\Services;

use App\Models\AdvertisingAccount;
use Exception;
use FacebookAds\Api;
use FacebookAds\Exception\FacebookException;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Page;
use Illuminate\Support\Facades\Log;

class FacebookAdsService
{
    protected $api;

    public function __construct(protected \App\Models\AdvertisingAccount $account)
    {
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
            $adAccount = new AdAccount('act_'.$this->account->account_id);
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
            Log::error('Facebook API Error: '.$e->getMessage());
            throw new Exception('Failed to fetch campaigns: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function createAndSchedulePost($pageId, array $postData)
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
            Log::error('Facebook API Error: '.$e->getMessage());
            throw new Exception('Failed to create and schedule post: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    // Add more methods for other Facebook Ads operations as needed
}
