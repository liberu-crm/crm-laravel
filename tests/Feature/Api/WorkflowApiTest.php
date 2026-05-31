<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkflowApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    public function test_can_list_workflows(): void
    {
        Workflow::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/workflows');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_can_create_workflow(): void
    {
        $payload = [
            'name' => 'Test Workflow',
            'description' => 'A test workflow',
            'triggers' => json_encode(['type' => 'event', 'event' => 'lead.created']),
            'actions' => json_encode([['type' => 'notification', 'channel' => 'email']]),
        ];

        $response = $this->postJson('/api/v1/workflows', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Test Workflow']);
    }

    public function test_can_show_workflow(): void
    {
        $workflow = Workflow::factory()->create();

        $response = $this->getJson("/api/v1/workflows/{$workflow->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $workflow->id]);
    }

    public function test_can_update_workflow(): void
    {
        $workflow = Workflow::factory()->create();

        $payload = [
            'name' => 'Updated Workflow',
            'triggers' => json_encode(['type' => 'event', 'event' => 'deal.closed']),
            'actions' => json_encode([['type' => 'notification', 'channel' => 'slack']]),
        ];

        $response = $this->putJson("/api/v1/workflows/{$workflow->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Workflow']);
    }

    public function test_can_delete_workflow(): void
    {
        $workflow = Workflow::factory()->create();

        $response = $this->deleteJson("/api/v1/workflows/{$workflow->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('workflows', ['id' => $workflow->id]);
    }

    public function test_requires_valid_triggers_json(): void
    {
        $payload = [
            'name' => 'Test',
            'triggers' => 'not-json',
            'actions' => json_encode([['type' => 'notification']]),
        ];

        $response = $this->postJson('/api/v1/workflows', $payload);

        $response->assertStatus(422);
    }

    public function test_requires_valid_actions_json(): void
    {
        $payload = [
            'name' => 'Test',
            'triggers' => json_encode(['type' => 'event']),
            'actions' => 'not-json',
        ];

        $response = $this->postJson('/api/v1/workflows', $payload);

        $response->assertStatus(422);
    }

    public function test_triggers_must_be_object(): void
    {
        $payload = [
            'name' => 'Test',
            'triggers' => json_encode('just a string'),
            'actions' => json_encode([['type' => 'notification']]),
        ];

        $response = $this->postJson('/api/v1/workflows', $payload);

        $response->assertStatus(422);
    }

    public function test_actions_must_be_array(): void
    {
        $payload = [
            'name' => 'Test',
            'triggers' => json_encode(['type' => 'event']),
            'actions' => json_encode('just a string'),
        ];

        $response = $this->postJson('/api/v1/workflows', $payload);

        $response->assertStatus(422);
    }
}
