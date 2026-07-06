<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WebhookApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    private function foreignTeamId(): int
    {
        return User::factory()->withPersonalTeam()->create()->currentTeam->id;
    }

    private function makeWebhook(int $teamId, array $attrs = []): Webhook
    {
        return Webhook::create(array_merge([
            'name' => 'Hook',
            'url' => 'https://hooks.example.com/endpoint',
            'events' => ['contact.created'],
            'secret' => 'seed-secret-1234567890',
            'is_active' => true,
            'team_id' => $teamId,
        ], $attrs));
    }

    public function test_api_requires_authentication(): void
    {
        $this->getJson('/api/v1/webhooks')->assertUnauthorized();
    }

    // -------------------------------------------------------------- index

    public function test_index_lists_only_own_team_webhooks(): void
    {
        $user = $this->actingUser();
        $this->makeWebhook($user->currentTeam->id);
        $this->makeWebhook($user->currentTeam->id);
        $this->makeWebhook($this->foreignTeamId());

        $this->getJson('/api/v1/webhooks')
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_index_does_not_leak_secret(): void
    {
        $user = $this->actingUser();
        $this->makeWebhook($user->currentTeam->id, ['secret' => 'top-secret-value-999']);

        $response = $this->getJson('/api/v1/webhooks')->assertOk();

        $this->assertStringNotContainsString('top-secret-value-999', $response->getContent());
    }

    // --------------------------------------------------------------- show

    public function test_can_show_own_webhook_with_secret(): void
    {
        $user = $this->actingUser();
        $webhook = $this->makeWebhook($user->currentTeam->id, ['secret' => 'owner-visible-secret-1']);

        $this->getJson("/api/v1/webhooks/{$webhook->id}")
            ->assertOk()
            ->assertJsonFragment(['secret' => 'owner-visible-secret-1']);
    }

    public function test_cannot_show_other_team_webhook_returns_404(): void
    {
        $this->actingUser();
        $webhook = $this->makeWebhook($this->foreignTeamId());

        $this->getJson("/api/v1/webhooks/{$webhook->id}")->assertNotFound();
    }

    // -------------------------------------------------------------- store

    public function test_can_create_webhook(): void
    {
        $user = $this->actingUser();

        $this->postJson('/api/v1/webhooks', [
            'name' => 'My Hook',
            'url' => 'https://hooks.example.com/new',
            'events' => ['contact.created', 'deal.won'],
        ])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'My Hook'])
            ->assertJsonStructure(['secret']);

        $this->assertDatabaseHas('webhooks', [
            'name' => 'My Hook',
            'team_id' => $user->currentTeam->id,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingUser();

        $this->postJson('/api/v1/webhooks', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'url', 'events']);
    }

    public function test_store_rejects_unknown_event(): void
    {
        $this->actingUser();

        $this->postJson('/api/v1/webhooks', [
            'name' => 'Hook',
            'url' => 'https://hooks.example.com/endpoint',
            'events' => ['not.a.real.event'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['events.0']);
    }

    // ------------------------------------------------------------- update

    public function test_can_update_own_webhook(): void
    {
        $user = $this->actingUser();
        $webhook = $this->makeWebhook($user->currentTeam->id, ['name' => 'Before']);

        $this->putJson("/api/v1/webhooks/{$webhook->id}", ['name' => 'After'])
            ->assertOk()
            ->assertJsonFragment(['name' => 'After']);

        $this->assertDatabaseHas('webhooks', ['id' => $webhook->id, 'name' => 'After']);
    }

    public function test_cannot_update_other_team_webhook_returns_404(): void
    {
        $this->actingUser();
        $webhook = $this->makeWebhook($this->foreignTeamId(), ['name' => 'Before']);

        $this->putJson("/api/v1/webhooks/{$webhook->id}", ['name' => 'Hijacked'])
            ->assertNotFound();

        $this->assertDatabaseHas('webhooks', ['id' => $webhook->id, 'name' => 'Before']);
    }

    // ------------------------------------------------------------ destroy

    public function test_can_delete_own_webhook(): void
    {
        $user = $this->actingUser();
        $webhook = $this->makeWebhook($user->currentTeam->id);

        $this->deleteJson("/api/v1/webhooks/{$webhook->id}")->assertNoContent();

        $this->assertDatabaseMissing('webhooks', ['id' => $webhook->id]);
    }

    public function test_cannot_delete_other_team_webhook_returns_404(): void
    {
        $this->actingUser();
        $webhook = $this->makeWebhook($this->foreignTeamId());

        $this->deleteJson("/api/v1/webhooks/{$webhook->id}")->assertNotFound();

        $this->assertDatabaseHas('webhooks', ['id' => $webhook->id]);
    }

    // -------------------------------------------------- regenerate secret

    public function test_can_regenerate_own_webhook_secret(): void
    {
        $user = $this->actingUser();
        $webhook = $this->makeWebhook($user->currentTeam->id, ['secret' => 'original-secret-000']);

        $response = $this->postJson("/api/v1/webhooks/{$webhook->id}/secret")
            ->assertOk()
            ->assertJsonStructure(['secret']);

        $this->assertNotSame('original-secret-000', $response->json('secret'));
        $this->assertNotSame('original-secret-000', $webhook->fresh()->secret);
    }

    public function test_cannot_regenerate_other_team_webhook_secret_returns_404(): void
    {
        $this->actingUser();
        $webhook = $this->makeWebhook($this->foreignTeamId(), ['secret' => 'original-secret-000']);

        $this->postJson("/api/v1/webhooks/{$webhook->id}/secret")->assertNotFound();

        $this->assertSame('original-secret-000', $webhook->fresh()->secret);
    }

    // -------------------------------------------------------------- events

    public function test_events_endpoint_lists_supported_events(): void
    {
        $this->actingUser();

        $this->getJson('/api/v1/webhooks/events')
            ->assertOk()
            ->assertJsonFragment(['events' => Webhook::EVENTS]);
    }
}
