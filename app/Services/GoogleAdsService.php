<?php

namespace App\Services;

use App\Models\ConnectedAccount;
use Google\Ads\GoogleAds\Lib\V14\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V14\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\V14\Services\CampaignOperation;
use Google\Ads\GoogleAds\V14\Resources\Campaign;
use Google\Ads\GoogleAds\V14\Enums\CampaignStatusEnum\CampaignStatus;
use Google\ApiCore\ApiException;
use Illuminate\Support\Facades\Log;

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
        try {
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
        } catch (ApiException $e) {
            Log::error('Google Ads API Error: ' . $e->getMessage());
            throw new \Exception('Failed to fetch campaigns: ' . $e->getMessage());
        }
    }

    public function createCampaign($accountId, $campaignData)
    {
        try {
            if (!isset($this->clients[$accountId])) {
                throw new \Exception("Google Ads account not found");
            }

            $client = $this->clients[$accountId];
            $customerId = $client->getLoginCustomerId();

            $campaign = new Campaign([
                'name' => $campaignData['name'],
                'status' => CampaignStatus::PAUSED,
                // Add other campaign settings as needed
            ]);

            $operation = new CampaignOperation();
            $operation->setCreate($campaign);

            $campaignServiceClient = $client->getCampaignServiceClient();
            $response = $campaignServiceClient->mutateCampaigns($customerId, [$operation]);

            $createdCampaign = $response->getResults()[0];
            return $createdCampaign->getResourceName();
        } catch (ApiException $e) {
            Log::error('Google Ads API Error: ' . $e->getMessage());
            throw new \Exception('Failed to create campaign: ' . $e->getMessage());
        }
    }

    public function updateCampaign($accountId, $campaignId, $campaignData)
    {
        try {
            if (!isset($this->clients[$accountId])) {
                throw new \Exception("Google Ads account not found");
            }

            $client = $this->clients[$accountId];
            $customerId = $client->getLoginCustomerId();

            $campaign = new Campaign([
                'resource_name' => $campaignId,
                'name' => $campaignData['name'],
                'status' => $campaignData['status'],
                // Add other updatable fields as needed
            ]);

            $operation = new CampaignOperation();
            $operation->setUpdate($campaign);
            $operation->setUpdateMask(['name', 'status']);

            $campaignServiceClient = $client->getCampaignServiceClient();
            $response = $campaignServiceClient->mutateCampaigns($customerId, [$operation]);

            $updatedCampaign = $response->getResults()[0];
            return $updatedCampaign->getResourceName();
        } catch (ApiException $e) {
            Log::error('Google Ads API Error: ' . $e->getMessage());
            throw new \Exception('Failed to update campaign: ' . $e->getMessage());
        }
    }

    public function deleteCampaign($accountId, $campaignId)
    {
        try {
            if (!isset($this->clients[$accountId])) {
                throw new \Exception("Google Ads account not found");
            }

            $client = $this->clients[$accountId];
            $customerId = $client->getLoginCustomerId();

            $operation = new CampaignOperation();
            $operation->setRemove($campaignId);

            $campaignServiceClient = $client->getCampaignServiceClient();
            $response = $campaignServiceClient->mutateCampaigns($customerId, [$operation]);

            $deletedCampaign = $response->getResults()[0];
            return $deletedCampaign->getResourceName();
        } catch (ApiException $e) {
            Log::error('Google Ads API Error: ' . $e->getMessage());
            throw new \Exception('Failed to delete campaign: ' . $e->getMessage());
        }
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