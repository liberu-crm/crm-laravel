<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
    }

    public function test_home_page_loads_for_unauthenticated_user(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_user_can_have_notifications(): void
    {
        $user = User::factory()->create();

        $user->notifications()->createMany([
            ['id' => Str::uuid(), 'type' => 'App\Notifications\TestNotification', 'data' => ['message' => 'Test 1']],
            ['id' => Str::uuid(), 'type' => 'App\Notifications\TestNotification', 'data' => ['message' => 'Test 2']],
        ]);

        $this->assertEquals(2, $user->notifications()->count());
        $this->assertEquals(2, $user->unreadNotifications()->count());
    }
}
