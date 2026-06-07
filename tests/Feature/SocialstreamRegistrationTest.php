<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JoelButcher\Socialstream\Providers;
use Laravel\Fortify\Features as FortifyFeatures;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SocialstreamRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that the social media platforms are available in the OAuth configuration create view.
     */
    #[Test]
    #[DataProvider('socialiteProvidersDataProvider')]
    public function test_users_get_redirected_correctly(string $provider): void
    {
        if (! Providers::enabled($provider)) {
            $this->markTestSkipped("Registration support with the $provider provider is not enabled.");
        }

        config()->set("services.$provider", [
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'redirect' => "http://localhost/oauth/$provider/callback",
        ]);

        $response = $this->get("/oauth/$provider");
        $response->assertRedirectContains($provider);
    }

    #[Test]
    #[DataProvider('socialiteProvidersDataProvider')]
    public function test_users_can_register_using_socialite_providers(string $socialiteProvider): void
    {
        if (! FortifyFeatures::enabled(FortifyFeatures::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        if (! Providers::enabled($socialiteProvider)) {
            $this->markTestSkipped("Registration support with the $socialiteProvider provider is not enabled.");
        }

        $user = (new User)
            ->map([
                'id' => 'abcdefgh',
                'nickname' => 'Jane',
                'name' => 'Jane Doe',
                'email' => 'janedoe@example.com',
                'avatar' => null,
                'avatar_original' => null,
            ])
            ->setToken('user-token')
            ->setRefreshToken('refresh-token')
            ->setExpiresIn(3600);

        // Use a generic mock to avoid class name issues with providers like 'twitter-oauth-2'
        $providerClass = 'Laravel\\Socialite\\Two\\'.$socialiteProvider.'Provider';
        $provider = preg_match('/[^a-zA-Z0-9_\\\\]/', $providerClass) ? Mockery::mock() : Mockery::mock($providerClass);
        $provider->shouldReceive('user')->once()->andReturn($user);

        Socialite::shouldReceive('driver')->once()->with($socialiteProvider)->andReturn($provider);

        // Use a fallback URL when the 'register' route is not defined
        $previousUrl = app('router')->has('register') ? route('register') : url('/register');

        $response = $this->withSession(['socialstream.previous_url' => $previousUrl])
            ->get("/oauth/$socialiteProvider/callback");

        $this->assertTrue($response->isRedirect(), 'Expected a redirect after successful OAuth authentication');
        $this->assertAuthenticated();
    }

    #[Test]
    public function test_socialstream_config_has_social_media_providers(): void
    {
        $providers = config('socialstream.providers', []);

        $enabled = array_filter($providers, fn ($p) => Providers::enabled($p));
        $all = Providers::all();
        $notEnabled = array_values(array_diff($all, $providers));

        foreach ($providers as $provider) {
            $this->assertContains($provider, $enabled);
        }

        foreach ($notEnabled as $provider) {
            $this->assertNotContains($provider, $enabled);
        }
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function socialiteProvidersDataProvider(): array
    {
        $config = require __DIR__.'/../../config/socialstream.php';

        return array_map(
            fn (string $provider) => [$provider],
            $config['providers'] ?? [],
        );
    }
}
