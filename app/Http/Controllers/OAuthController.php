<?php

namespace App\Http\Controllers;

use App\Models\ConnectedAccount;
use App\Models\OAuthConfiguration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OAuthController extends Controller
{
    protected $providerScopes = [
        'facebook' => [
            'scopes' => ['email', 'pages_show_list', 'pages_read_engagement', 'pages_manage_posts', 'publish_to_groups', 'instagram_basic', 'instagram_content_publish'],
        ],
        'linkedin-openid' => [
            'scopes' => ['openid', 'profile', 'email', 'w_member_social'],
        ],
        'twitter-oauth-2' => [
            'scopes' => ['tweet.read', 'tweet.write', 'users.read', 'offline.access'],
        ],
        'google' => [
            'scopes' => [
                'https://www.googleapis.com/auth/youtube.upload',
                'https://www.googleapis.com/auth/youtube',
            ],
        ],
    ];

    public function redirect($provider)
    {
        $config = OAuthConfiguration::getConfig($provider);

        if (!$config) {
            return redirect()->route('oauth.configurations.index')
                ->with('error', 'OAuth provider not configured. Please add your ' . ucfirst($provider) . ' credentials first.');
        }

        $driver = Socialite::driver($provider);

        if (isset($this->providerScopes[$provider])) {
            $driver->scopes($this->providerScopes[$provider]['scopes']);
        }

        if ($provider === 'google') {
            $driver->with(['access_type' => 'offline', 'prompt' => 'consent']);
        }

        return $driver->redirect();
    }

    public function callback($provider)
    {
        try {
            $config = OAuthConfiguration::getConfig($provider);

            if (!$config) {
                return redirect()->route('oauth.configurations.index')
                    ->with('error', 'OAuth provider not configured.');
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
                    'expires_at' => isset($socialiteUser->expiresIn)
                        ? Carbon::now()->addSeconds($socialiteUser->expiresIn)
                        : null,
                    'metadata' => $this->getProviderMetadata($provider, $socialiteUser),
                ]
            );

            return redirect()->route('oauth.configurations.index')
                ->with('success', ucfirst($provider) . ' account connected successfully.');
        } catch (\Exception $e) {
            Log::error("OAuth callback failed for {$provider}: " . $e->getMessage());
            return redirect()->route('oauth.configurations.index')
                ->with('error', 'Failed to connect ' . ucfirst($provider) . ' account: ' . $e->getMessage());
        }
    }

    protected function getProviderMetadata($provider, $socialiteUser): array
    {
        $metadata = [
            'email' => $socialiteUser->getEmail(),
            'avatar' => $socialiteUser->getAvatar(),
        ];

        switch ($provider) {
            case 'facebook':
                $metadata['pages'] = $this->getFacebookPages($socialiteUser->token);
                break;
            case 'linkedin-openid':
                $metadata['organizations'] = $this->getLinkedInOrganizations($socialiteUser->token);
                break;
            case 'google':
                $metadata['channels'] = $this->getYouTubeChannels($socialiteUser->token);
                break;
        }

        return $metadata;
    }

    protected function getFacebookPages(string $accessToken): array
    {
        $graphVersion = config('services.facebook.graph_version', 'v18.0');
        try {
            $response = \Illuminate\Support\Facades\Http::get("https://graph.facebook.com/{$graphVersion}/me/accounts", [
                'access_token' => $accessToken,
                'fields' => 'id,name,access_token,instagram_business_account',
            ]);

            return $response->successful() ? ($response->json('data') ?? []) : [];
        } catch (\Exception $e) {
            Log::warning('Failed to fetch Facebook pages: ' . $e->getMessage());
            return [];
        }
    }

    protected function getLinkedInOrganizations(string $accessToken): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
                ->get('https://api.linkedin.com/v2/organizationalEntityAcls', [
                    'q' => 'roleAssignee',
                    'role' => 'ADMINISTRATOR',
                    'state' => 'APPROVED',
                ]);

            return $response->successful() ? ($response->json('elements') ?? []) : [];
        } catch (\Exception $e) {
            Log::warning('Failed to fetch LinkedIn organizations: ' . $e->getMessage());
            return [];
        }
    }

    protected function getYouTubeChannels(string $accessToken): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->get('https://www.googleapis.com/youtube/v3/channels', [
                    'part' => 'snippet,contentDetails',
                    'mine' => 'true',
                ]);

            return $response->successful() ? ($response->json('items') ?? []) : [];
        } catch (\Exception $e) {
            Log::warning('Failed to fetch YouTube channels: ' . $e->getMessage());
            return [];
        }
    }
}
