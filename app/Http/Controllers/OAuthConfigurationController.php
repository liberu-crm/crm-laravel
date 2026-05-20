<?php

namespace App\Http\Controllers;

use App\Models\OAuthConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class OAuthConfigurationController extends Controller
{
    /**
     * Maps the user-facing service name to the Socialite driver name
     * and the scopes needed for posting content.
     */
    protected $serviceToDriver = [
        'facebook'  => 'facebook',
        'twitter'   => 'twitter-oauth-2',
        'instagram' => 'facebook',        // Instagram posting uses Facebook Graph API
        'linkedin'  => 'linkedin-openid',
        'youtube'   => 'google',          // YouTube uses Google OAuth
        'google'    => 'google',
        'gmail'     => 'google',
        'whatsapp'  => 'whatsapp',
        'outlook'   => 'microsoft',
    ];

    protected $serviceScopes = [
        'facebook' => [
            'email',
            'pages_show_list',
            'pages_read_engagement',
            'pages_manage_posts',
            'publish_to_groups',
            'instagram_basic',
            'instagram_content_publish',
        ],
        'twitter' => [
            'tweet.read',
            'tweet.write',
            'users.read',
            'offline.access',
        ],
        'instagram' => [
            'email',
            'pages_show_list',
            'pages_read_engagement',
            'instagram_basic',
            'instagram_content_publish',
        ],
        'linkedin' => [
            'openid',
            'profile',
            'email',
            'w_member_social',
        ],
        'youtube' => [
            'https://www.googleapis.com/auth/youtube.upload',
            'https://www.googleapis.com/auth/youtube',
        ],
        'google' => [
            'https://www.googleapis.com/auth/gmail.readonly',
            'https://www.googleapis.com/auth/calendar',
        ],
    ];

    public function index()
    {
        $configurations = OAuthConfiguration::where('user_id', Auth::id())->get();
        return view('oauth.configurations.index', compact('configurations'));
    }

    public function create()
    {
        return view('oauth.configurations.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_name' => 'required|string',
            'account_name' => 'required|string',
            'additional_settings' => 'nullable|array',
        ]);

        $config = new OAuthConfiguration($validated);
        $config->user_id = Auth::id();
        $config->save();

        return redirect()->route('oauth.authenticate', [
            'service' => $config->service_name,
            'configId' => $config->id,
        ]);
    }

    public function authenticate($service, $configId)
    {
        session(['oauth_config_id' => $configId]);

        $driver = $this->serviceToDriver[$service] ?? $service;

        try {
            $socialite = Socialite::driver($driver);

            if (isset($this->serviceScopes[$service])) {
                $socialite->scopes($this->serviceScopes[$service]);
            }

            if ($driver === 'google') {
                $socialite->with(['access_type' => 'offline', 'prompt' => 'consent']);
            }

            return $socialite->redirect();
        } catch (\Exception $e) {
            Log::error("OAuth redirect failed for {$service}: " . $e->getMessage());
            return redirect()->route('oauth.configurations.index')
                ->with('error', 'Failed to start OAuth for ' . ucfirst($service) . '. Please ensure the credentials are configured in settings.');
        }
    }

    public function callback($service)
    {
        try {
            $configId = session('oauth_config_id');
            $config = OAuthConfiguration::findOrFail($configId);

            $driver = $this->serviceToDriver[$service] ?? $service;
            $socialiteUser = Socialite::driver($driver)->user();

            $additionalSettings = array_merge($config->additional_settings ?? [], [
                'provider_id' => $socialiteUser->getId(),
                'email'       => $socialiteUser->getEmail(),
                'name'        => $socialiteUser->getName(),
                'avatar'      => $socialiteUser->getAvatar(),
            ]);

            $updateData = [
                'is_active'          => true,
                'additional_settings' => $additionalSettings,
            ];

            // Store tokens if the columns exist (added by migration)
            if (\Illuminate\Support\Facades\Schema::hasColumn('oauth_configurations', 'access_token')) {
                $updateData['access_token'] = $socialiteUser->token;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('oauth_configurations', 'refresh_token')) {
                $updateData['refresh_token'] = $socialiteUser->refreshToken;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('oauth_configurations', 'token_expires_at')) {
                $updateData['token_expires_at'] = isset($socialiteUser->expiresIn)
                    ? now()->addSeconds((int) $socialiteUser->expiresIn)
                    : null;
            }

            $config->update($updateData);

            return redirect()->route('oauth.configurations.index')
                ->with('success', ucfirst($service) . ' account connected successfully!');
        } catch (\Exception $e) {
            Log::error("OAuth callback failed for {$service}: " . $e->getMessage());
            return redirect()->route('oauth.configurations.index')
                ->with('error', 'Failed to connect account: ' . $e->getMessage());
        }
    }

    public function destroy(OAuthConfiguration $configuration)
    {
        $configuration->delete();
        return redirect()->route('oauth.configurations.index')
            ->with('success', 'Configuration removed successfully');
    }
}
