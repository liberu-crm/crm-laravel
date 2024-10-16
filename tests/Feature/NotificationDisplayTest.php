<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\DatabaseNotification;

class NotificationDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_count_display_for_authenticated_user()
    {
        $user = User::factory()->create();
        Notification::fake();

        // Create some notifications for the user
        $user->notifications()->createMany([
            ['type' => 'App\Notifications\TestNotification', 'data' => ['message' => 'Test 1']],
            ['type' => 'App\Notifications\TestNotification', 'data' => ['message' => 'Test 2']],
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('2', false); // Check if the notification count is displayed
        $response->assertSee('View all notifications', false);
    }

    public function test_notification_display_for_unauthenticated_user()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('View all notifications', false);
        $response->assertSee('Login', false); // Check if the login link is displayed instead
    }
}