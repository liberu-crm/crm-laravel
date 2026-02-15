<?php

namespace App\Services\OAuth;

use App\Models\ConnectedAccount;
use App\Models\OAuthConfiguration;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OAuthManager
{
    protected $client;
    
    protected $providers = [
        'mailchimp' => [
            'authorize_url' => 'https://login.mailchimp.com/oauth2/authorize',
            'token_url' => 'https://login.mailchimp.com/oauth2/token',
            'metadata_url' => 'https://login.mailchimp.com/oauth2/metadata',
            'scopes' => [],
        ],
        'stripe' => [
            'authorize_url' => 'https://connect.stripe.com/oauth/authorize',
            'token_url' => 'https://connect.stripe.com/oauth/token',
            'scopes' => ['read_write'],
        ],
        'google' => [
            'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'scopes' => [
                'https://www.googleapis.com/auth/gmail.readonly',
                'https://www.googleapis.com/auth/calendar',
                'https://www.googleapis.com/auth/adwords',
            ],
        ],
        'microsoft' => [
            'authorize_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
            'token_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
            'scopes' => [
                'https://outlook.office.com/Mail.Read',
                'https://outlook.office.com/Calendars.ReadWrite',
            ],
        ],
        'facebook' => [
            'authorize_url' => 'https://www.facebook.com/v18.0/dialog/oauth',
            'token_url' => 'https://graph.facebook.com/v18.0/oauth/access_token',
            'scopes' => ['email', 'pages_messaging', 'ads_management', 'ads_read'],
        ],
        'linkedin' => [
            'authorize_url' => 'https://www.linkedin.com/oauth/v2/authorization',
            'token_url' => 'https://www.linkedin.com/oauth/v2/accessToken',
            'scopes' => ['r_liteprofile', 'r_emailaddress', 'w_member_social', 'r_ads', 'r_ads_reporting'],
        ],
        'twitter' => [
            'authorize_url' => 'https://twitter.com/i/oauth2/authorize',
            'token_url' => 'https://api.twitter.com/2/oauth2/token',
            'scopes' => ['tweet.read', 'users.read', 'offline.access'],
        ],
        'zoom' => [
            'authorize_url' => 'https://zoom.us/oauth/authorize',
            'token_url' => 'https://zoom.us/oauth/token',
            'scopes' => ['meeting:write', 'meeting:read', 'user:read'],
        ],
    ];

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => true,
        ]);
    }

    /**
     * Generate authorization URL for a provider
     */
    public function getAuthorizationUrl(string $provider, array $additionalScopes = []): string
    {
        if (!isset($this->providers[$provider])) {
            throw new \InvalidArgumentException("Unsupported provider: {$provider}");
        }

        $config = OAuthConfiguration::getConfig($provider);
        if (!$config) {
            throw new \RuntimeException("OAuth configuration not found for provider: {$provider}");
        }

        $providerConfig = $this->providers[$provider];
        $scopes = array_merge($providerConfig['scopes'], $additionalScopes);
        
        $params = [
            'client_id' => $config->client_id,
            'redirect_uri' => $this->getRedirectUri($provider),
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'state' => $this->generateState($provider),
        ];

        // Provider-specific parameters
        if ($provider === 'google') {
            $params['access_type'] = 'offline';
            $params['prompt'] = 'consent';
        } elseif ($provider === 'mailchimp') {
            unset($params['scope']); // MailChimp doesn't use scopes
        } elseif ($provider === 'twitter') {
            $params['code_challenge'] = 'challenge';
            $params['code_challenge_method'] = 'plain';
        }

        return $providerConfig['authorize_url'] . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken(string $provider, string $code): array
    {
        if (!isset($this->providers[$provider])) {
            throw new \InvalidArgumentException("Unsupported provider: {$provider}");
        }

        $config = OAuthConfiguration::getConfig($provider);
        if (!$config) {
            throw new \RuntimeException("OAuth configuration not found for provider: {$provider}");
        }

        $providerConfig = $this->providers[$provider];
        
        $params = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $config->client_id,
            'client_secret' => $config->client_secret,
            'redirect_uri' => $this->getRedirectUri($provider),
        ];

        try {
            $response = $this->client->post($providerConfig['token_url'], [
                'form_params' => $params,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            
            // Get additional metadata for some providers
            if ($provider === 'mailchimp') {
                $metadata = $this->getMailChimpMetadata($data['access_token']);
                $data['metadata'] = $metadata;
            }

            return $data;
        } catch (\Exception $e) {
            Log::error("OAuth token exchange failed for {$provider}: " . $e->getMessage());
            throw new \RuntimeException("Failed to exchange code for token: " . $e->getMessage());
        }
    }

    /**
     * Refresh an access token
     */
    public function refreshToken(ConnectedAccount $account): bool
    {
        if (!$account->refresh_token) {
            Log::warning("No refresh token available for account {$account->id}");
            return false;
        }

        $provider = $account->provider;
        if (!isset($this->providers[$provider])) {
            throw new \InvalidArgumentException("Unsupported provider: {$provider}");
        }

        $config = OAuthConfiguration::getConfig($provider);
        if (!$config) {
            throw new \RuntimeException("OAuth configuration not found for provider: {$provider}");
        }

        $providerConfig = $this->providers[$provider];
        
        $params = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $account->refresh_token,
            'client_id' => $config->client_id,
            'client_secret' => $config->client_secret,
        ];

        try {
            $response = $this->client->post($providerConfig['token_url'], [
                'form_params' => $params,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            
            $account->update([
                'token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? $account->refresh_token,
                'expires_at' => isset($data['expires_in']) ? 
                    Carbon::now()->addSeconds($data['expires_in']) : null,
            ]);

            Log::info("Successfully refreshed token for account {$account->id}");
            return true;
        } catch (\Exception $e) {
            Log::error("OAuth token refresh failed for account {$account->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Save connected account from OAuth response
     */
    public function saveConnectedAccount(string $provider, array $tokenData, ?int $userId = null): ConnectedAccount
    {
        $userId = $userId ?? Auth::id();
        
        $accountData = [
            'user_id' => $userId,
            'provider' => $provider,
            'token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? null,
            'expires_at' => isset($tokenData['expires_in']) ? 
                Carbon::now()->addSeconds($tokenData['expires_in']) : null,
        ];

        // Provider-specific data extraction
        if ($provider === 'mailchimp' && isset($tokenData['metadata'])) {
            $accountData['provider_id'] = $tokenData['metadata']['user_id'] ?? null;
            $accountData['name'] = $tokenData['metadata']['accountname'] ?? null;
            $accountData['email'] = $tokenData['metadata']['login']['email'] ?? null;
        }

        return ConnectedAccount::updateOrCreate(
            [
                'user_id' => $userId,
                'provider' => $provider,
            ],
            $accountData
        );
    }

    /**
     * Check if token needs refresh
     */
    public function needsRefresh(ConnectedAccount $account): bool
    {
        if (!$account->expires_at) {
            return false;
        }

        // Refresh if token expires in less than 5 minutes
        return $account->expires_at->subMinutes(5)->isPast();
    }

    /**
     * Get redirect URI for a provider
     */
    protected function getRedirectUri(string $provider): string
    {
        return route('oauth.callback', ['provider' => $provider]);
    }

    /**
     * Generate state token for CSRF protection
     */
    protected function generateState(string $provider): string
    {
        return base64_encode(json_encode([
            'provider' => $provider,
            'timestamp' => time(),
            'token' => bin2hex(random_bytes(16)),
        ]));
    }

    /**
     * Get MailChimp metadata
     */
    protected function getMailChimpMetadata(string $accessToken): array
    {
        try {
            $response = $this->client->get($this->providers['mailchimp']['metadata_url'], [
                'headers' => [
                    'Authorization' => "OAuth {$accessToken}",
                    'Accept' => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error("Failed to get MailChimp metadata: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all supported providers
     */
    public function getSupportedProviders(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Check if provider is supported
     */
    public function isProviderSupported(string $provider): bool
    {
        return isset($this->providers[$provider]);
    }
}
