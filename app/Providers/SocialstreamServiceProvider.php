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
    protected $socialProviders = [
        'facebook' => [
            'scopes' => ['email', 'pages_show_list', 'pages_read_engagement', 'instagram_basic'],
            'optional_scopes' => ['publish_to_groups', 'groups_access_member_info'],
        ],
        'linkedin' => [
            'scopes' => ['r_liteprofile', 'r_emailaddress', 'w_member_social', 'r_organization_admin'],
        ],
        'twitter' => [
            'scopes' => ['tweet.read', 'tweet.write', 'users.read', 'offline.access'],
        ],
        'instagram' => [
            'scopes' => ['instagram_basic', 'instagram_content_publish', 'pages_read_engagement'],
        ],
        'youtube' => [
            'scopes' => ['https://www.googleapis.com/auth/youtube.readonly', 'https://www.googleapis.com/auth/youtube.upload'],
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
     * Configure Socialite providers with database-stored settings.
     */
    protected function configureSocialiteProviders(): void
    {
        $providers = OAuthConfiguration::all();

        foreach ($providers as $provider) {
            Socialite::extend($provider->service_name, function () use ($provider) {
                $config = [
                    'client_id' => $provider->client_id,
                    'client_secret' => $provider->client_secret,
                    'redirect' => config('app.url') . '/oauth/' . $provider->service_name . '/callback',
                ];

                if ($provider->service_name === 'twilio') {
                    return new TwilioProvider(
                        $this->app['request'], 
                        $config['client_id'],
                        $config['client_secret'],
                        $config['redirect']
                    );
                }

                $socialiteProvider = Socialite::buildProvider(
                    "Laravel\\Socialite\\Two\\" . ucfirst($provider->service_name) . 'Provider',
                    $config
                );

                // Add scopes if defined for this provider
                if (isset($this->socialProviders[$provider->service_name])) {
                    $providerConfig = $this->socialProviders[$provider->service_name];
                    
                    if (isset($providerConfig['scopes'])) {
                        $socialiteProvider->scopes($providerConfig['scopes']);
                    }
                    
                    if (isset($providerConfig['optional_scopes'])) {
                        $socialiteProvider->with(['optional_scopes' => $providerConfig['optional_scopes']]);
                    }
                }

                return $socialiteProvider;
            });
        }
    }
}
