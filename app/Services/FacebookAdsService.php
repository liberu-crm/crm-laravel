<?php

namespace App\Services;

use App\Models\AdvertisingAccount;
use FacebookAds\Api;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;

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
    }

    // Add more methods for other Facebook Ads operations as needed
}