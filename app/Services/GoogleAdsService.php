<?php

namespace App\Services;

use App\Models\AdvertisingAccount;
use Google\Ads\GoogleAds\Lib\V14\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V14\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;

class GoogleAdsService
{
    protected $client;

    public function __construct(AdvertisingAccount $account)
    {
        $this->client = $this->createClient($account);
    }

    protected function createClient(AdvertisingAccount $account)
    {
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->withClientId(config('services.google_ads.client_id'))
            ->withClientSecret(config('services.google_ads.client_secret'))
            ->withRefreshToken($account->refresh_token)
            ->build();

        return (new GoogleAdsClientBuilder())
            ->withOAuth2Credential($oAuth2Credential)
            ->withDeveloperToken(config('services.google_ads.developer_token'))
            ->withLoginCustomerId($account->account_id)
            ->build();
    }

    public function getCampaigns()
    {
        $customerService = $this->client->getCustomerService();
        $customer = $customerService->getCustomer($this->client->getLoginCustomerId());

        $query = "SELECT campaign.id, campaign.name, campaign.status FROM campaign ORDER BY campaign.id";
        $stream = $this->client->getGoogleAdsServiceClient()->search($customer->getResourceName(), $query);

        $campaigns = [];
        foreach ($stream->iterateAllElements() as $googleAdsRow) {
            $campaign = $googleAdsRow->getCampaign();
            $campaigns[] = [
                'id' => $campaign->getId(),
                'name' => $campaign->getName(),
                'status' => $campaign->getStatus(),
            ];
        }

        return $campaigns;
    }

    // Add more methods for other Google Ads operations as needed
}