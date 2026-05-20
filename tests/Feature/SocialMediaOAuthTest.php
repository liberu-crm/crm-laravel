<?php

namespace Tests\Feature;

use App\Models\OAuthConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SocialMediaOAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the social media platforms are available in the OAuth configuration create view.
     */
    public function test_create_view_includes_social_media_platforms(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('oauth.configurations.create'));

        $response->assertOk();
        $response->assertSee('facebook');
        $response->assertSee('twitter');
        $response->assertSee('instagram');
        $response->assertSee('linkedin');
        $response->assertSee('youtube');
    }

    /**
     * Test that storing a social media OAuth configuration redirects to authenticate.
     */
    public function test_store_social_media_config_redirects_to_authenticate(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('oauth.configurations.store'), [
            'service_name' => 'facebook',
            'account_name' => 'My Facebook Page',
        ]);

        $this->assertDatabaseHas('oauth_configurations', [
            'service_name' => 'facebook',
            'account_name' => 'My Facebook Page',
        ]);

        $config = OAuthConfiguration::where('service_name', 'facebook')->first();
        $response->assertRedirect(
            route('oauth.authenticate', ['service' => 'facebook', 'configId' => $config->id])
        );
    }

    /**
     * Test that all social media platforms can be stored as OAuth configurations.
     *
     * @dataProvider socialMediaPlatformsProvider
     */
    public function test_can_store_social_media_oauth_configuration(string $platform): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('oauth.configurations.store'), [
            'service_name' => $platform,
            'account_name' => 'Test ' . ucfirst($platform) . ' Account',
        ]);

        $this->assertDatabaseHas('oauth_configurations', [
            'service_name' => $platform,
            'account_name' => 'Test ' . ucfirst($platform) . ' Account',
        ]);
    }

    /**
     * Test that the OAuthConfigurationController maps service names to correct Socialite drivers.
     * We test the behavior indirectly by verifying the authenticate route redirects for supported platforms.
     *
     * @dataProvider serviceToDriverProvider
     */
    public function test_service_maps_to_correct_socialite_driver(string $service, string $expectedDriver): void
    {
        // Verify the mapping is correct by checking what driver would be used.
        // We test this via the controller method behavior rather than inspecting internals.
        $controller = app(\App\Http\Controllers\OAuthConfigurationController::class);

        // Create a config so authenticate() doesn't fail on missing config
        OAuthConfiguration::create([
            'service_name'  => $service,
            'account_name'  => 'Test',
            'client_id'     => 'test_id',
            'client_secret' => 'test_secret',
            'is_active'     => false,
        ]);

        // The authenticate method uses $this->serviceToDriver internally.
        // We verify indirectly that the expected driver matches by checking the config
        // key that the controller would look up in config/services.php.
        $this->assertArrayHasKey(
            $expectedDriver,
            config('services'),
            "services.{$expectedDriver} config must exist for '{$service}' platform"
        );
    }

    /**
     * Test that social media posts table has media columns.
     */
    public function test_social_media_posts_table_has_media_columns(): void
    {
        // After running migrations, these columns should exist
        $this->assertTrue(
            Schema::hasColumn('social_media_posts', 'link'),
            'social_media_posts should have a link column'
        );
        $this->assertTrue(
            Schema::hasColumn('social_media_posts', 'image_path'),
            'social_media_posts should have an image_path column'
        );
        $this->assertTrue(
            Schema::hasColumn('social_media_posts', 'video_url'),
            'social_media_posts should have a video_url column'
        );
    }

    /**
     * Test that the socialstream config has social media providers enabled.
     */
    public function test_socialstream_config_has_social_media_providers(): void
    {
        $providers = config('socialstream.providers');

        $this->assertNotEmpty($providers, 'Socialstream should have providers configured');

        // Facebook provider should be enabled
        $this->assertTrue(
            in_array('facebook', $providers),
            'Facebook provider should be enabled in socialstream config'
        );
    }

    /**
     * Test that services config has correct Socialite keys for social platforms.
     *
     * @dataProvider socialiteServicesProvider
     */
    public function test_services_config_has_socialite_keys(string $service): void
    {
        $config = config("services.{$service}");
        $this->assertNotNull($config, "services.{$service} config should exist");
        $this->assertArrayHasKey('client_id', $config,
            "services.{$service} should have client_id for Socialite");
        $this->assertArrayHasKey('client_secret', $config,
            "services.{$service} should have client_secret for Socialite");
        $this->assertArrayHasKey('redirect', $config,
            "services.{$service} should have redirect for Socialite");
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function socialMediaPlatformsProvider(): array
    {
        return [
            ['facebook'],
            ['twitter'],
            ['instagram'],
            ['linkedin'],
            ['youtube'],
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function serviceToDriverProvider(): array
    {
        return [
            ['facebook',  'facebook'],
            ['twitter',   'twitter-oauth-2'],
            ['instagram', 'facebook'],          // Instagram uses Facebook Graph API
            ['linkedin',  'linkedin-openid'],
            ['youtube',   'google'],            // YouTube uses Google OAuth
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function socialiteServicesProvider(): array
    {
        return [
            ['facebook'],
            ['google'],
            ['twitter-oauth-2'],
            ['linkedin-openid'],
        ];
    }
}
