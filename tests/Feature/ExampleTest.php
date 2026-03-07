<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the root route ("/") returns a successful response.
     */
    public function test_the_root_route_returns_a_successful_response(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    /**
     * Test the "/app" route returns a successful response or redirects authenticated users.
     */
    public function test_the_app_route_returns_a_successful_response(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->current_team_id = $user->ownedTeams->first()->id;
        $user->save();

        $response = $this->actingAs($user)->get('/app');
        $response->assertStatus(302); // Filament redirects /app to /app/dashboard
    }

    /**
     * Test the "/admin" route returns a successful response for admin users.
     */
    public function test_the_admin_route_returns_a_successful_response(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->withPersonalTeam()->create();
        $user->current_team_id = $user->ownedTeams->first()->id;
        $user->save();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(302); // Filament redirects /admin to /admin/dashboard
    }
}
