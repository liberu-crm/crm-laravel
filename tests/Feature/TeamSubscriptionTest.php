<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use App\Models\TeamSubscription;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;

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

    public function testCreateTeamSubscription()
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

    public function testCancelTeamSubscription()
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

    public function testTeamSubscriptionBelongsToTeam()
    {
        $team = Team::factory()->create();
        $subscription = TeamSubscription::factory()->create(['team_id' => $team->id]);

        $this->assertEquals($team->id, $subscription->team->id);
    }

    public function testStripeServiceMock()
    {
        $this->stripeService->shouldReceive('createSubscription')
            ->once()
            ->andReturn(['status' => 'active']);

        $result = app(StripeService::class)->createSubscription([]);

        $this->assertEquals('active', $result['status']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
