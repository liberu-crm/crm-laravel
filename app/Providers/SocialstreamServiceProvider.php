<?php

namespace App\Providers;

use App\Actions\Socialstream\CreateConnectedAccount;
use App\Actions\Socialstream\CreateUserFromProvider;
use App\Actions\Socialstream\GenerateRedirectForProvider;
use App\Actions\Socialstream\HandleInvalidState;
use App\Actions\Socialstream\ResolveSocialiteUser;
use App\Actions\Socialstream\UpdateConnectedAccount;
use App\Models\OAuthConfiguration;
use App\Services\OAuth\TwilioProvider;
use Illuminate\Support\ServiceProvider;
use JoelButcher\Socialstream\Socialstream;
use Laravel\Socialite\Facades\Socialite;

class SocialstreamServiceProvider extends ServiceProvider
{
    /**
     * Maps service_name stored in oauth_configurations to the Socialite driver name
     * and the scopes needed for social media posting.
     */
    protected $socialProviders = [
        'facebook' => [
            'driver' => 'facebook',
            'scopes' => [
                'email',
                'pages_show_list',
                'pages_read_engagement',
                'pages_manage_posts',
                'publish_to_groups',
                'instagram_basic',
                'instagram_content_publish',
            ],
        ],
        'twitter' => [
            'driver' => 'twitter-oauth-2',
            'scopes' => ['tweet.read', 'tweet.write', 'users.read', 'offline.access'],
        ],
        'linkedin' => [
            'driver' => 'linkedin-openid',
            'scopes' => ['openid', 'profile', 'email', 'w_member_social'],
        ],
        'instagram' => [
            // Instagram posting uses the Facebook Graph API via the facebook driver
            'driver' => 'facebook',
            'scopes' => [
                'email',
                'pages_show_list',
                'pages_read_engagement',
                'instagram_basic',
                'instagram_content_publish',
            ],
        ],
        'youtube' => [
            // YouTube uses Google OAuth with YouTube-specific scopes
            'driver' => 'google',
            'scopes' => [
                'https://www.googleapis.com/auth/youtube.upload',
                'https://www.googleapis.com/auth/youtube',
            ],
        ],
        'google' => [
            'driver' => 'google',
            'scopes' => [
                'https://www.googleapis.com/auth/gmail.readonly',
                'https://www.googleapis.com/auth/calendar',
            ],
        ],
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Socialstream::resolvesSocialiteUsersUsing(ResolveSocialiteUser::class);
        Socialstream::createUsersFromProviderUsing(CreateUserFromProvider::class);
        Socialstream::createConnectedAccountsUsing(CreateConnectedAccount::class);
        Socialstream::updateConnectedAccountsUsing(UpdateConnectedAccount::class);
        Socialstream::handlesInvalidStateUsing(HandleInvalidState::class);
        Socialstream::generatesProvidersRedirectsUsing(GenerateRedirectForProvider::class);

        $this->configureSocialiteProviders();
    }

    /**
     * Configure Socialite providers with database-stored credentials.
     * Providers like Twitter, Instagram, and YouTube are mapped to their
     * correct Socialite driver names with posting-appropriate scopes.
     */
    protected function configureSocialiteProviders(): void
    {
        try {
            $providers = OAuthConfiguration::where('is_active', true)->get();
        } catch (\Exception $e) {
            // Database may not be available yet (e.g. during migrations)
            return;
        }

        foreach ($providers as $provider) {
            $serviceName = $provider->service_name;

            if ($serviceName === 'twilio') {
                Socialite::extend($serviceName, function () use ($provider) {
                    return new TwilioProvider(
                        $this->app['request'],
                        $provider->client_id,
                        $provider->client_secret,
                        config('app.url') . '/oauth/twilio/callback'
                    );
                });
                continue;
            }

            if (!isset($this->socialProviders[$serviceName])) {
                continue;
            }

            $providerConfig = $this->socialProviders[$serviceName];
            $driverName = $providerConfig['driver'];
            $scopes = $providerConfig['scopes'];

            // Register the service_name as an alias that delegates to the correct driver
            Socialite::extend($serviceName, function () use ($provider, $driverName, $scopes) {
                $config = [
                    'client_id' => $provider->client_id,
                    'client_secret' => $provider->client_secret,
                    'redirect' => config('app.url') . '/oauth/' . $provider->service_name . '/callback',
                ];

                $driver = Socialite::buildProvider(
                    $this->getSocialiteProviderClass($driverName),
                    $config
                );

                if (!empty($scopes)) {
                    $driver->scopes($scopes);
                }

                if ($driverName === 'google') {
                    $driver->with(['access_type' => 'offline', 'prompt' => 'consent']);
                }

                return $driver;
            });
        }
    }

    /**
     * Map a driver name to the fully-qualified Socialite provider class.
     */
    protected function getSocialiteProviderClass(string $driverName): string
    {
        $map = [
            'facebook' => \Laravel\Socialite\Two\FacebookProvider::class,
            'google' => \Laravel\Socialite\Two\GoogleProvider::class,
            'linkedin-openid' => \Laravel\Socialite\Two\LinkedInOpenIdProvider::class,
            'twitter-oauth-2' => \Laravel\Socialite\Two\TwitterProvider::class,
            'github' => \Laravel\Socialite\Two\GithubProvider::class,
            'gitlab' => \Laravel\Socialite\Two\GitlabProvider::class,
            'bitbucket' => \Laravel\Socialite\Two\BitbucketProvider::class,
        ];

        if (!isset($map[$driverName])) {
            throw new \InvalidArgumentException("Unsupported Socialite driver: {$driverName}");
        }

        return $map[$driverName];
    }
}
