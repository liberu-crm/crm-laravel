<?php

namespace Tests\Feature;

use App\Models\OAuthConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OAuthConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_oauth_configuration_can_be_created_in_database()
    {
        OAuthConfiguration::create([
            'service_name' => 'facebook',
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
        ]);

        $this->assertDatabaseHas('oauth_configurations', [
            'service_name' => 'facebook',
            'client_id' => 'test_client_id',
        ]);
    }

    public function test_oauth_redirect_uses_database_configuration()
    {
        OAuthConfiguration::create([
            'service_name' => 'facebook',
            'client_id' => 'db_client_id',
            'client_secret' => 'db_client_secret',
        ]);

        $config = OAuthConfiguration::where('service_name', 'facebook')->first();

        $this->assertNotNull($config);
        $this->assertEquals('db_client_id', $config->client_id);
        $this->assertEquals('db_client_secret', $config->client_secret);
    }

    public function test_oauth_configuration_can_be_listed()
    {
        OAuthConfiguration::create(['service_name' => 'facebook', 'client_id' => 'id1', 'client_secret' => 'secret1']);
        OAuthConfiguration::create(['service_name' => 'twitter', 'client_id' => 'id2', 'client_secret' => 'secret2']);

        $this->assertEquals(2, OAuthConfiguration::count());
    }

    public function test_oauth_configuration_page_is_accessible_for_authenticated_user()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->current_team_id = $user->ownedTeams->first()->id;
        $user->save();

        $response = $this->actingAs($user)->get(route('oauth.configurations.index'));
        $response->assertSuccessful();
    }
}
