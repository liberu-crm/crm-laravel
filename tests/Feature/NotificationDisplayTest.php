<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class NotificationDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads_for_authenticated_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
    }

    public function test_home_page_loads_for_unauthenticated_user()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_user_can_have_notifications()
    {
        $user = User::factory()->create();

        $user->notifications()->createMany([
            ['id' => \Illuminate\Support\Str::uuid(), 'type' => 'App\Notifications\TestNotification', 'data' => ['message' => 'Test 1']],
            ['id' => \Illuminate\Support\Str::uuid(), 'type' => 'App\Notifications\TestNotification', 'data' => ['message' => 'Test 2']],
        ]);

        $this->assertEquals(2, $user->notifications()->count());
        $this->assertEquals(2, $user->unreadNotifications()->count());
    }
}
