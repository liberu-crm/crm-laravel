<?php

namespace App\Services;

use App\Models\AdvertisingAccount;
use GuzzleHttp\Client;

class LinkedInAdsService
{
    protected $client;
    protected $account;

    public function __construct(AdvertisingAccount $account)
    {
        $this->account = $account;
        $this->client = new Client([
            'base_uri' => 'https://api.linkedin.com/v2/',
            'headers' => [
                'Authorization' => 'Bearer ' . $account->access_token,
                'X-Restli-Protocol-Version' => '2.0.0',
            ],
        ]);
    }

    public function getCampaigns()
    {
        $response = $this->client->get("adCampaignsV2?q=search&search=(account:(values:List({$this->account->account_id})))");
        $data = json_decode($response->getBody(), true);

        $campaigns = [];
        foreach ($data['elements'] as $campaign) {
            $campaigns[] = [
                'id' => $campaign['id'],
                'name' => $campaign['name'],
                'status' => $campaign['status'],
            ];
        }

        return $campaigns;
    }

    // Add more methods for other LinkedIn Ads operations as needed
}