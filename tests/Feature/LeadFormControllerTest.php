<?php

namespace Tests\Feature;

use App\Models\LeadForm;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadFormControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_form_submission_triggers_workflow()
    {
        $leadForm = LeadForm::factory()->create();
        
        $workflow = Workflow::factory()->create([
            'triggers' => ['type' => 'lead_created'],
            'actions' => ['type' => 'send_email', 'template' => 'welcome'],
        ]);

        $formData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone_number' => '1234567890',
        ];

        $response = $this->postJson("/api/lead-forms/{$leadForm->id}/submit", $formData);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'lead_id']);

        $this->assertDatabaseHas('leads', [
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('jobs', [
            'queue' => 'default',
            'payload' => $this->stringContains('ExecuteWorkflowAction'),
        ]);
    }
}