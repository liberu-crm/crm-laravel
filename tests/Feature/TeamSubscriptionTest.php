

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
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);

        $this->stripeService->shouldReceive('createSubscription')
            ->once()
            ->andReturn(TeamSubscription::factory()->create([
                'team_id' => $team->id,
                'stripe_status' => 'active',
                'trial_ends_at' => now()->addDays(14),
            ]));

        $response = $this->actingAs($user)->postJson("/api/teams/{$team->id}/subscription", [
            'payment_method_id' => 'pm_card_visa',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('team_subscriptions', [
            'team_id' => $team->id,
            'stripe_status' => 'active',
        ]);
    }

    public function testCancelTeamSubscription()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $subscription = TeamSubscription::factory()->create([
            'team_id' => $team->id,
            'stripe_status' => 'active',
        ]);

        $this->stripeService->shouldReceive('cancelSubscription')
            ->once()
            ->with($subscription);

        $response = $this->actingAs($user)->deleteJson("/api/teams/{$team->id}/subscription");

        $response->assertStatus(200);
        $this->assertTrue($team->fresh()->subscription->hasExpired());
    }

    public function testEnforceTeamUserLimit()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $subscription = TeamSubscription::factory()->create([
            'team_id' => $team->id,
            'stripe_status' => 'active',
            'quantity' => 1,
        ]);

        config(['services.stripe.max_team_users' => 2]);

        // First additional user should succeed
        $response = $this->actingAs($user)->postJson("/api/teams/{$team->id}/members", [
            'email' => $this->faker->email,
            'role' => 'editor',
        ]);
        $response->assertStatus(200);

        // Second additional user should fail
        $response = $this->actingAs($user)->postJson("/api/teams/{$team->id}/members", [
            'email' => $this->faker->email,
            'role' => 'editor',
        ]);
        $response->assertStatus(422);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}