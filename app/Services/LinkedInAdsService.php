<?php

namespace App\Services;

use App\Models\AdvertisingAccount;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class LinkedInAdsService
{
    protected \GuzzleHttp\Client $client;

    public function __construct(protected \App\Models\AdvertisingAccount $account)
    {
        $this->client = new Client([
            'base_uri' => 'https://api.linkedin.com/v2/',
            'headers' => [
                'Authorization' => 'Bearer '.$this->account->access_token,
                'X-Restli-Protocol-Version' => '2.0.0',
            ],
        ]);
    }

    public function getCampaigns()
    {
        try {
            $response = $this->client->get("adCampaignsV2?q=search&search=(account:(values:List({$this->account->account_id})))");
            $data = json_decode((string) $response->getBody(), true);

            $campaigns = [];
            foreach ($data['elements'] as $campaign) {
                $campaigns[] = [
                    'id' => $campaign['id'],
                    'name' => $campaign['name'],
                    'status' => $campaign['status'],
                ];
            }

            return $campaigns;
        } catch (GuzzleException $e) {
            Log::error('LinkedIn API Error: '.$e->getMessage());
            throw new Exception('Failed to fetch campaigns: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function createAndSchedulePost($organizationId, array $postData)
    {
        try {
            $payload = [
                'author' => "urn:li:organization:$organizationId",
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => [
                            'text' => $postData['message'],
                        ],
                        'shareMediaCategory' => 'NONE',
                    ],
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
                ],
                'distribution' => [
                    'linkedInDistributionTarget' => [
                        'visibleToGuest' => true,
                    ],
                ],
            ];

            if (isset($postData['scheduled_time'])) {
                $payload['scheduledTime'] = $postData['scheduled_time'];
            }

            if (isset($postData['link'])) {
                $payload['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'ARTICLE';
                $payload['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [
                    [
                        'status' => 'READY',
                        'originalUrl' => $postData['link'],
                    ],
                ];
            }

            $response = $this->client->post('ugcPosts', [
                'json' => $payload,
            ]);

            $result = json_decode((string) $response->getBody(), true);

            return [
                'id' => $result['id'],
            ];
        } catch (GuzzleException $e) {
            Log::error('LinkedIn API Error: '.$e->getMessage());
            throw new Exception('Failed to create and schedule post: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    // Add more methods for other LinkedIn Ads operations as needed
}
