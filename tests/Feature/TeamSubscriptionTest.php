<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\TeamSubscription;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Tests\TestCase;

class TeamSubscriptionTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stripeService = Mockery::mock(StripeService::class);
        $this->app->instance(StripeService::class, $this->stripeService);
    }

    public function test_create_team_subscription()
    {
        $team = Team::factory()->create();

        $subscription = TeamSubscription::factory()->create([
            'team_id' => $team->id,
            'stripe_status' => 'active',
        ]);

        $this->assertDatabaseHas('team_subscriptions', [
            'team_id' => $team->id,
            'stripe_status' => 'active',
        ]);
    }

    public function test_cancel_team_subscription()
    {
        $team = Team::factory()->create();
        $subscription = TeamSubscription::factory()->create([
            'team_id' => $team->id,
            'stripe_status' => 'active',
        ]);

        $subscription->update(['stripe_status' => 'cancelled', 'ends_at' => now()]);

        $this->assertDatabaseHas('team_subscriptions', [
            'id' => $subscription->id,
            'stripe_status' => 'cancelled',
        ]);
    }

    public function test_team_subscription_belongs_to_team()
    {
        $team = Team::factory()->create();
        $subscription = TeamSubscription::factory()->create(['team_id' => $team->id]);

        $this->assertEquals($team->id, $subscription->team->id);
    }

    public function test_stripe_service_mock()
    {
        $team = Team::factory()->create();

        $subscription = TeamSubscription::factory()->make(['team_id' => $team->id, 'stripe_status' => 'active']);

        $this->stripeService->shouldReceive('createSubscription')
            ->once()
            ->andReturn($subscription);

        $result = app(StripeService::class)->createSubscription($team, 'pm_test_fake');

        $this->assertEquals('active', $result->stripe_status);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
