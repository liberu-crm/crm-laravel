<?php

namespace App\Http\Controllers;

use App\Models\OAuthConfiguration;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;

class OAuthController extends Controller
{
    public function redirect($provider)
    {
        $config = OAuthConfiguration::getConfig($provider);

        if (!$config) {
            return redirect()->route('login')->with('error', 'OAuth provider not configured.');
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider)
    {
        $config = OAuthConfiguration::getConfig($provider);

        if (!$config) {
            return redirect()->route('login')->with('error', 'OAuth provider not configured.');
        }

        $user = Socialite::driver($provider)->user();

        // Here you would typically find or create a user based on the OAuth data
        // and log them in. For brevity, we'll just return the user object.
        return response()->json($user);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AdvertisingAccount;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;

class OAuthController extends Controller
{
    protected $providers = [
        'google' => [
            'scopes' => ['https://www.googleapis.com/auth/adwords'],
            'additional_parameters' => [
                'access_type' => 'offline',
                'prompt' => 'consent'
            ]
        ],
        'facebook' => [
            'scopes' => ['ads_management', 'ads_read'],
        ],
        'linkedin' => [
            'scopes' => ['r_ads', 'r_ads_reporting'],
        ],
        'microsoft' => [
            'scopes' => ['https://ads.microsoft.com/ads.manage'],
        ]
    ];

    public function redirect($provider)
    {
        if (!array_key_exists($provider, $this->providers)) {
            return redirect()->route('advertising-accounts.index')
                ->with('error', 'Unsupported provider');
        }

        $config = $this->providers[$provider];
        
        return Socialite::driver($provider)
            ->scopes($config['scopes'])
            ->with($config['additional_parameters'] ?? [])
            ->redirect();
    }

    public function callback($provider, Request $request)
    {
        try {
            $socialiteUser = Socialite::driver($provider)->user();
            
            $account = new AdvertisingAccount([
                'name' => $request->session()->get('account_name', $socialiteUser->getName()),
                'platform' => $this->getPlatformName($provider),
                'account_id' => $socialiteUser->getId(),
                'access_token' => $socialiteUser->token,
                'refresh_token' => $socialiteUser->refreshToken,
                'status' => true,
                'metadata' => [
                    'email' => $socialiteUser->getEmail(),
                    'avatar' => $socialiteUser->getAvatar(),
                ],
            ]);

            if ($provider === 'google') {
                $account->developer_token = config('services.google_ads.developer_token');
                $account->client_id = config('services.google_ads.client_id');
                $account->client_secret = config('services.google_ads.client_secret');
            }

            $account->save();

            return redirect()->route('advertising-accounts.index')
                ->with('success', 'Advertising account connected successfully');
        } catch (\Exception $e) {
            return redirect()->route('advertising-accounts.index')
                ->with('error', 'Failed to connect advertising account: ' . $e->getMessage());
        }
    }

    protected function getPlatformName($provider)
    {
        return [
            'google' => 'Google Ads',
            'facebook' => 'Facebook Ads',
            'linkedin' => 'LinkedIn Ads',
            'microsoft' => 'Microsoft Ads',
        ][$provider] ?? $provider;
    }
}

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