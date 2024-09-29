<?php

namespace App\Providers;

use App\Actions\Socialstream\CreateConnectedAccount;
use App\Actions\Socialstream\CreateUserFromProvider;
use App\Actions\Socialstream\GenerateRedirectForProvider;
use App\Actions\Socialstream\HandleInvalidState;
use App\Actions\Socialstream\ResolveSocialiteUser;
use App\Actions\Socialstream\UpdateConnectedAccount;
use App\Models\OAuthConfiguration;
use Illuminate\Support\ServiceProvider;
use JoelButcher\Socialstream\Socialstream;
use Laravel\Socialite\Facades\Socialite;

class SocialstreamServiceProvider extends ServiceProvider
{
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

                return Socialite::buildProvider(
                    "Laravel\\Socialite\\Two\\" . ucfirst($provider->service_name) . 'Provider',
                    $config
                );
            });
        }
    }
}
