<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_workflow_creation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $workflowData = [
            'name' => 'Test Workflow',
            'description' => 'A test workflow',
            'triggers' => json_encode(['type' => 'lead_created']),
            'actions' => json_encode(['type' => 'send_email', 'template' => 'welcome']),
        ];

        $response = $this->postJson('/api/workflows', $workflowData);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'name', 'description', 'triggers', 'actions']);
    }

    public function test_workflow_execution()
    {
        $workflow = Workflow::factory()->create([
            'triggers' => ['type' => 'lead_created'],
            'actions' => ['type' => 'send_email', 'template' => 'welcome'],
        ]);

        $lead = Lead::factory()->create();

        // Simulate lead creation
        $this->post('/api/leads', $lead->toArray());

        // Assert that the workflow was triggered
        $this->assertDatabaseHas('jobs', [
            'queue' => 'default',
            'payload' => $this->stringContains('ExecuteWorkflowAction'),
        ]);
    }

    public function test_workflow_customization()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $workflow = Workflow::factory()->create();

        $updatedData = [
            'name' => 'Updated Workflow',
            'triggers' => json_encode(['type' => 'deal_closed']),
            'actions' => json_encode(['type' => 'update_contact', 'status' => 'customer']),
        ];

        $response = $this->putJson("/api/workflows/{$workflow->id}", $updatedData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Workflow',
                'triggers' => ['type' => 'deal_closed'],
                'actions' => ['type' => 'update_contact', 'status' => 'customer'],
            ]);
    }
}