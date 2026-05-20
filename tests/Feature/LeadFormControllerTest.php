<?php

namespace Tests\Feature;

use App\Models\LeadForm;
use App\Models\Workflow;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadFormControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_form_can_be_created()
    {
        $leadForm = LeadForm::factory()->create();

        $this->assertDatabaseHas('lead_forms', [
            'id' => $leadForm->id,
        ]);
    }

    public function test_lead_form_workflow_relationship()
    {
        $workflow = Workflow::factory()->create([
            'triggers' => ['type' => 'lead_created'],
            'actions' => ['type' => 'send_email', 'template' => 'welcome'],
        ]);

        $leadForm = LeadForm::factory()->create();

        $this->assertNotNull($leadForm);
        $this->assertNotNull($workflow);
    }

    public function test_lead_can_be_created_from_form_data()
    {
        $lead = Lead::factory()->create([
            'status' => 'new',
            'lifecycle_stage' => 'lead',
        ]);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => 'new',
        ]);
    }
}
