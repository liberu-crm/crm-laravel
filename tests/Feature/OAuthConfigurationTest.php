<?php

namespace Tests\Feature;

use App\Models\OAuthConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OAuthConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_oauth_configuration_can_be_created()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->post('/admin/oauth-configurations', [
            'service_name' => 'facebook',
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'additional_settings' => ['key' => 'value'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('oauth_configurations', [
            'service_name' => 'facebook',
            'client_id' => 'test_client_id',
        ]);
    }

    public function test_oauth_configuration_is_used_in_services_config()
    {
        OAuthConfiguration::create([
            'service_name' => 'facebook',
            'client_id' => 'db_client_id',
            'client_secret' => 'db_client_secret',
        ]);

        $facebookConfig = config('services.facebook');
        $this->assertEquals('db_client_id', $facebookConfig['app_id']);
        $this->assertEquals('db_client_secret', $facebookConfig['app_secret']);
    }

    public function test_oauth_redirect_uses_database_configuration()
    {
        OAuthConfiguration::create([
            'service_name' => 'facebook',
            'client_id' => 'db_client_id',
            'client_secret' => 'db_client_secret',
        ]);

        $response = $this->get('/oauth/facebook');
        $response->assertRedirect();
        $this->assertStringContainsString('db_client_id', $response->getTargetUrl());
    }
}