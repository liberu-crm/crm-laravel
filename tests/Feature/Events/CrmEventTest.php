<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Events\DealClosed;
use App\Events\NewLead;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Team;
use App\Models\User;
use App\Notifications\CRMEventNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CrmEventTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Team, 1: User} */
    private function teamWithOwner(): array
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $owner->id]);

        return [$team, $owner];
    }

    public function test_new_lead_notifies_only_its_team_users(): void
    {
        Notification::fake();

        [$teamA, $ownerA] = $this->teamWithOwner();
        [, $ownerB] = $this->teamWithOwner();

        $lead = Lead::factory()->create(['team_id' => $teamA->id]);

        event(new NewLead($lead));

        Notification::assertSentTo($ownerA, CRMEventNotification::class);
        // Leak guard: a user from another team must NOT be notified.
        Notification::assertNotSentTo($ownerB, CRMEventNotification::class);
    }

    public function test_deal_closed_notifies_only_its_team_users(): void
    {
        Notification::fake();

        [$teamA, $ownerA] = $this->teamWithOwner();
        [, $ownerB] = $this->teamWithOwner();

        $deal = Deal::factory()->create(['team_id' => $teamA->id, 'stage' => 'won']);

        event(new DealClosed($deal));

        Notification::assertSentTo($ownerA, CRMEventNotification::class);
        // Leak guard: a user from another team must NOT be notified.
        Notification::assertNotSentTo($ownerB, CRMEventNotification::class);
    }

    public function test_new_lead_to_array_shape(): void
    {
        Notification::fake(); // creating the lead auto-fires NewLead → listener

        [$team] = $this->teamWithOwner();
        $lead = Lead::factory()->create(['team_id' => $team->id, 'status' => 'new']);

        $payload = (new NewLead($lead))->toArray();

        $this->assertSame($lead->id, $payload['id']);
        $this->assertSame('new', $payload['status']);
        $this->assertSame($team->id, $payload['team_id']);
    }

    public function test_creating_a_lead_dispatches_new_lead(): void
    {
        Event::fake([NewLead::class]);

        [$team] = $this->teamWithOwner();
        $lead = Lead::factory()->create(['team_id' => $team->id]);

        Event::assertDispatched(NewLead::class, fn (NewLead $e): bool => $e->lead->is($lead));
    }

    public function test_updating_deal_stage_to_won_dispatches_deal_closed(): void
    {
        Event::fake([DealClosed::class]);

        [$team] = $this->teamWithOwner();
        $deal = Deal::factory()->create(['team_id' => $team->id, 'stage' => 'proposal']);

        $deal->update(['stage' => 'won']);

        Event::assertDispatched(DealClosed::class, fn (DealClosed $e): bool => $e->deal->is($deal));
    }
}
