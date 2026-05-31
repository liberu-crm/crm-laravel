<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\CRMEventNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_are_created_for_crm_events(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $event = 'NewLead';
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];

        $user->notify(new CRMEventNotification($event, $data));

        Notification::assertSentTo(
            $user,
            CRMEventNotification::class
        );
    }

    public function test_notification_can_be_stored_in_database(): void
    {
        $user = User::factory()->create();

        $user->notifications()->create([
            'id' => Str::uuid(),
            'type' => CRMEventNotification::class,
            'data' => ['event' => 'NewLead', 'data' => ['name' => 'John Doe']],
        ]);

        $this->assertEquals(1, $user->notifications()->count());
    }

    public function test_notifications_can_be_marked_as_read(): void
    {
        $user = User::factory()->create();

        $notification = $user->notifications()->create([
            'id' => Str::uuid(),
            'type' => CRMEventNotification::class,
            'data' => ['event' => 'NewLead', 'data' => ['name' => 'John Doe']],
        ]);

        $notification->markAsRead();

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    public function test_unread_notification_count_is_tracked(): void
    {
        $user = User::factory()->create();

        $user->notifications()->createMany([
            ['id' => Str::uuid(), 'type' => CRMEventNotification::class, 'data' => ['event' => 'Event1']],
            ['id' => Str::uuid(), 'type' => CRMEventNotification::class, 'data' => ['event' => 'Event2']],
        ]);

        $this->assertEquals(2, $user->unreadNotifications()->count());
    }
}
