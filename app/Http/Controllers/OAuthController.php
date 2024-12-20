<?php

namespace App\Http\Controllers;

use App\Models\ConnectedAccount;
use App\Models\OAuthConfiguration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;

class OAuthController extends Controller
{
    public function redirect($provider)
    {
        $config = OAuthConfiguration::getConfig($provider);

        if (!$config) {
            return redirect()->route('profile.show')->with('error', 'OAuth provider not configured.');
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider)
    {
        try {
            $config = OAuthConfiguration::getConfig($provider);

            if (!$config) {
                return redirect()->route('profile.show')->with('error', 'OAuth provider not configured.');
            }

            $socialiteUser = Socialite::driver($provider)->user();
            
            $connectedAccount = ConnectedAccount::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'provider' => $provider,
                    'provider_id' => $socialiteUser->getId(),
                ],
                [
                    'name' => $socialiteUser->getName(),
                    'nickname' => $socialiteUser->getNickname(),
                    'email' => $socialiteUser->getEmail(),
                    'avatar_path' => $socialiteUser->getAvatar(),
                    'token' => $socialiteUser->token,
                    'refresh_token' => $socialiteUser->refreshToken,
                    'token_secret' => $socialiteUser->tokenSecret ?? null,
                    'expires_at' => isset($socialiteUser->expiresIn) ? 
                        Carbon::now()->addSeconds($socialiteUser->expiresIn) : null,
                    'metadata' => $this->getProviderMetadata($provider, $socialiteUser)
                ]
            );

            // For providers that require additional API calls to get pages/channels
            $this->fetchAdditionalData($provider, $connectedAccount, $socialiteUser);

            return redirect()->route('profile.show')
                ->with('success', ucfirst($provider) . ' account connected successfully.');

        } catch (\Exception $e) {
            return redirect()->route('profile.show')
                ->with('error', 'Failed to connect ' . ucfirst($provider) . ' account: ' . $e->getMessage());
        }
    }

    protected function getProviderMetadata($provider, $socialiteUser)
    {
        $metadata = [];

        switch ($provider) {
            case 'facebook':
                $metadata['pages'] = $this->getFacebookPages($socialiteUser);
                break;
            case 'linkedin':
                $metadata['companies'] = $this->getLinkedInCompanies($socialiteUser);
                break;
            case 'youtube':
                $metadata['channels'] = $this->getYouTubeChannels($socialiteUser);
                break;
        }

        return $metadata;
    }

    protected function getFacebookPages($socialiteUser)
    {
        $fb = new \Facebook\Facebook([
            'app_id' => config('services.facebook.client_id'),
            'app_secret' => config('services.facebook.client_secret'),
            'default_access_token' => $socialiteUser->token,
        ]);

        try {
            $response = $fb->get('/me/accounts');
            return $response->getDecodedBody()['data'];
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getLinkedInCompanies($socialiteUser)
    {
        $client = new \GuzzleHttp\Client();
        
        try {
            $response = $client->get('https://api.linkedin.com/v2/organizationalEntityAcls?q=roleAssignee', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $socialiteUser->token,
                    'X-Restli-Protocol-Version' => '2.0.0',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getYouTubeChannels($socialiteUser)
    {
        $client = new \Google_Client();
        $client->setAccessToken($socialiteUser->token);
        
        try {
            $youtube = new \Google_Service_YouTube($client);
            $channels = $youtube->channels->listChannels('snippet,contentDetails', [
                'mine' => true
            ]);

            return $channels->getItems();
        } catch (\Exception $e) {
            return [];
        }
    }

    //
    protected function fetchAdditionalData($provider, $connectedAccount, $socialiteUser)
    {
        switch ($provider) {
            case 'instagram':
                $this->fetchInstagramBusinessAccounts($connectedAccount, $socialiteUser);
                break;
            case 'twitter':
                $this->fetchTwitterFollowers($connectedAccount, $socialiteUser);
                break;
        }
    }

    protected function fetchInstagramBusinessAccounts($connectedAccount, $socialiteUser)
    {
        // Implementation for fetching Instagram business accounts
        // This would typically involve using the Facebook Graph API
    }

    protected function fetchTwitterFollowers($connectedAccount, $socialiteUser)
    {
        // Implementation for fetching Twitter follower count
        // This would typically involve using the Twitter API
    }
}