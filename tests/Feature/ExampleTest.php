<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test the root route ("/") returns a successful response.
     */
    public function test_the_root_route_returns_a_successful_response(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    /**
     * Test the "/app" route returns a successful response.
     */
    public function test_the_app_route_returns_a_successful_response(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/app');
        $response->assertStatus(200);
    }

    /**
     * Test the "/admin" route returns a successful response.
     */
    public function test_the_admin_route_returns_a_successful_response(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(200);
    }
}
