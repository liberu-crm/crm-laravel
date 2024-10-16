<?php

namespace Tests\Unit\Notifications;

use App\Notifications\CRMEventNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class CRMEventNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_notification_content()
    {
        $user = User::factory()->create();
        $event = 'NewLead';
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];

        $notification = new CRMEventNotification($event, $data);
        $mailMessage = $notification->toMail($user);

        $this->assertEquals("CRM Event: {$event}", $mailMessage->subject);
        $this->assertStringContainsString("A new CRM event has occurred: {$event}", $mailMessage->introLines[0]);
        $this->assertStringContainsString("Details: " . json_encode($data), $mailMessage->introLines[1]);
    }

    public function test_in_app_notification_data_structure()
    {
        $user = User::factory()->create();
        $event = 'DealClosed';
        $data = ['deal_id' => 123, 'amount' => 10000];

        $notification = new CRMEventNotification($event, $data);
        $arrayData = $notification->toArray($user);

        $this->assertEquals($event, $arrayData['event']);
        $this->assertEquals($data, $arrayData['data']);
    }
}