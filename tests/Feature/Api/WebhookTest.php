<?php

namespace Tests\Feature\Api;

use App\Models\Webhook;
use App\Models\User;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    protected function withProductionEnv(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
    }

    protected function withTestingEnv(): void
    {
        $this->app->detectEnvironment(fn () => 'testing');
    }

    public function test_accepts_public_https_url_in_non_production()
    {
        Http::fake();

        $response = $this->postJson('/api/v1/webhooks', [
            'name'   => 'Test',
            'url'    => 'https://hooks.example.com/endpoint',
            'events' => ['contact.created'],
        ]);

        $response->assertStatus(201);
    }

    public function test_rejects_localhost_url_in_production()
    {
        $this->withProductionEnv();

        $response = $this->postJson('/api/v1/webhooks', [
            'name'   => 'Test',
            'url'    => 'http://localhost:9000/hook',
            'events' => ['contact.created'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['url']);

        $this->withTestingEnv();
    }

    public function test_rejects_raw_ip_url_in_production()
    {
        $this->withProductionEnv();

        $response = $this->postJson('/api/v1/webhooks', [
            'name'   => 'Test',
            'url'    => 'http://192.168.1.1/webhook',
            'events' => ['contact.created'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['url']);

        $this->withTestingEnv();
    }

    public function test_rejects_non_https_url_in_production()
    {
        $this->withProductionEnv();

        $response = $this->postJson('/api/v1/webhooks', [
            'name'   => 'Test',
            'url'    => 'http://example.com/hook',
            'events' => ['contact.created'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['url']);

        $this->withTestingEnv();
    }

    public function test_service_skips_ssrf_check_in_non_production()
    {
        Http::fake();

        $webhook = Webhook::create([
            'name'      => 'Test',
            'url'       => 'https://example.com/hook',
            'events'    => ['contact.created'],
            'secret'    => 'test-secret-12345678',
            'is_active' => true,
        ]);

        $service = app(WebhookService::class);
        $service->send($webhook, 'contact.created', []);

        Http::assertSent(function (\Illuminate\Http\Client\Request $r) {
            return $r->url() === 'https://example.com/hook';
        });
    }

    public function test_update_rejects_localhost_url_in_production()
    {
        $this->withProductionEnv();

        $webhook = Webhook::create([
            'name'      => 'Test',
            'url'       => 'https://example.com/hook',
            'events'    => ['contact.created'],
            'secret'    => 'test-secret-12345678',
            'is_active' => true,
        ]);

        $response = $this->putJson("/api/v1/webhooks/{$webhook->id}", [
            'url' => 'http://localhost:9000/hook',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['url']);

        $this->withTestingEnv();
    }

    public function test_uses_https_for_production_webhooks()
    {
        $this->withProductionEnv();

        $response = $this->postJson('/api/v1/webhooks', [
            'name'   => 'Test',
            'url'    => 'https://hooks.example.com/safe',
            'events' => ['contact.created'],
        ]);

        $response->assertStatus(201);

        $this->withTestingEnv();
    }

}
