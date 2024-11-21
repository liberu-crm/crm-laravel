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