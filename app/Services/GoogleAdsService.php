<?php

namespace App\Services;

use App\Models\ConnectedAccount;
use Google\Ads\GoogleAds\Lib\V14\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V14\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;

class GoogleAdsService
{
    protected $clients = [];

    public function __construct()
    {
        $this->initializeClients();
    }

    protected function initializeClients()
    {
        $accounts = ConnectedAccount::ofType('google_ads')->get();
        foreach ($accounts as $account) {
            $this->clients[$account->id] = $this->createClient($account);
        }
    }

    protected function createClient(ConnectedAccount $account)
    {
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->withClientId(config('services.google_ads.client_id'))
            ->withClientSecret(config('services.google_ads.client_secret'))
            ->withRefreshToken($account->token)
            ->build();

        return (new GoogleAdsClientBuilder())
            ->withOAuth2Credential($oAuth2Credential)
            ->withDeveloperToken(config('services.google_ads.developer_token'))
            ->withLoginCustomerId($account->provider_id)
            ->build();
    }

    public function getCampaigns($accountId)
    {
        if (!isset($this->clients[$accountId])) {
            throw new \Exception("Google Ads account not found");
        }

        $client = $this->clients[$accountId];
        $customerService = $client->getCustomerService();
        $customer = $customerService->getCustomer($client->getLoginCustomerId());

        $query = "SELECT campaign.id, campaign.name, campaign.status FROM campaign ORDER BY campaign.id";
        $stream = $client->getGoogleAdsServiceClient()->search($customer->getResourceName(), $query);

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

    public function createCampaign($accountId, $campaignData)
    {
        if (!isset($this->clients[$accountId])) {
            throw new \Exception("Google Ads account not found");
        }

        $client = $this->clients[$accountId];
        // Implement campaign creation logic here
    }

    public function updateCampaign($accountId, $campaignId, $campaignData)
    {
        if (!isset($this->clients[$accountId])) {
            throw new \Exception("Google Ads account not found");
        }

        $client = $this->clients[$accountId];
        // Implement campaign update logic here
    }

    public function deleteCampaign($accountId, $campaignId)
    {
        if (!isset($this->clients[$accountId])) {
            throw new \Exception("Google Ads account not found");
        }

        $client = $this->clients[$accountId];
        // Implement campaign deletion logic here
    }

    public function getAllConnectedAccounts()
    {
        return ConnectedAccount::ofType('google_ads')->get();
    }

    public function getPrimaryAccount()
    {
        return ConnectedAccount::ofType('google_ads')->primary()->first();
    }
}