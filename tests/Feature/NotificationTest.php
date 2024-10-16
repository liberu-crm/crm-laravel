<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Notifications\CRMEventNotification;
use Illuminate\Support\Facades\Notification;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_are_created_for_crm_events()
    {
        Notification::fake();

        $user = User::factory()->create();
        $event = 'NewLead';
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];

        $user->notify(new CRMEventNotification($event, $data));

        Notification::assertSentTo(
            $user,
            CRMEventNotification::class,
            function ($notification) use ($event, $data) {
                return $notification->event === $event && $notification->data === $data;
            }
        );
    }

    public function test_users_can_view_in_app_notifications()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $event = 'NewLead';
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];

        $user->notify(new CRMEventNotification($event, $data));

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $response->assertSee($event);
    }

    public function test_users_can_mark_notifications_as_read()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $notification = $user->notifications()->create([
            'type' => CRMEventNotification::class,
            'data' => ['event' => 'NewLead', 'data' => ['name' => 'John Doe']],
        ]);

        $response = $this->post("/notifications/{$notification->id}/mark-as-read");

        $response->assertStatus(200);
        $this->assertNotNull($notification->fresh()->read_at);
    }
}