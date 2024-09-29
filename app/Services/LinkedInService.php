<?php

namespace App\Services;

use App\Models\ConnectedAccount;
use Illuminate\Support\Facades\Http;

class LinkedInService
{
    protected $apiUrl = 'https://api.linkedin.com/v2/';

    public function getProfile(ConnectedAccount $account)
    {
        $response = Http::withToken($account->token)
            ->get($this->apiUrl . 'me');

        return $response->json();
    }

    public function sharePost(ConnectedAccount $account, $content)
    {
        $response = Http::withToken($account->token)
            ->post($this->apiUrl . 'ugcPosts', [
                'author' => 'urn:li:person:' . $account->provider_id,
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => [
                            'text' => $content
                        ],
                        'shareMediaCategory' => 'NONE'
                    ]
                ],
                'visibility' => [

                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'
                ]
            ]);

        return $response->json();
    }

    public function getAllConnectedAccounts()
    {
        return ConnectedAccount::ofType('linkedin')->get();
    }

    public function getPrimaryAccount()
    {
        return ConnectedAccount::ofType('linkedin')->primary()->first();
    }
}