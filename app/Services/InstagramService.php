<?php

namespace App\Services;

use App\Models\ConnectedAccount;
use Illuminate\Support\Facades\Http;

class InstagramService
{
    protected $apiUrl = 'https://graph.instagram.com/v12.0/';

    public function getRecentMedia(ConnectedAccount $account)
    {
        $response = Http::get($this->apiUrl . 'me/media', [
            'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username',
            'access_token' => $account->token,
        ]);

        return $response->json()['data'] ?? [];
    }

    public function postMedia(ConnectedAccount $account, $imageUrl, $caption)
    {
        // Note: Posting to Instagram requires a Facebook Page connected to an Instagram Professional account
        // This is a simplified example and may need to be adjusted based on the actual Instagram Graph API requirements
        $response = Http::post($this->apiUrl . $account->provider_id . '/media', [
            'image_url' => $imageUrl,
            'caption' => $caption,
            'access_token' => $account->token,
        ]);

        $mediaObjectId = $response->json()['id'];

        $publishResponse = Http::post($this->apiUrl . $account->provider_id . '/media_publish', [
            'creation_id' => $mediaObjectId,
            'access_token' => $account->token,
        ]);

        return $publishResponse->json();
    }

    public function getAllConnectedAccounts()
    {
        return ConnectedAccount::ofType('instagram')->get();
    }

    public function getPrimaryAccount()
    {
        return ConnectedAccount::ofType('instagram')->primary()->first();
    }
}