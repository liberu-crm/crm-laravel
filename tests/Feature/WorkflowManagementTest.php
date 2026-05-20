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
        $workflow = Workflow::factory()->create([
            'name' => 'Test Workflow',
            'description' => 'A test workflow',
        ]);

        $this->assertDatabaseHas('workflows', [
            'id' => $workflow->id,
            'name' => 'Test Workflow',
            'description' => 'A test workflow',
        ]);
    }

    public function test_workflow_customization()
    {
        $workflow = Workflow::factory()->create([
            'name' => 'Original Workflow',
        ]);

        $workflow->update([
            'name' => 'Updated Workflow',
        ]);

        $this->assertDatabaseHas('workflows', [
            'id' => $workflow->id,
            'name' => 'Updated Workflow',
        ]);
    }

    public function test_workflow_deletion()
    {
        $workflow = Workflow::factory()->create();

        $workflow->delete();

        $this->assertDatabaseMissing('workflows', ['id' => $workflow->id]);
    }

    public function test_workflow_has_triggers()
    {
        $workflow = Workflow::factory()->create();

        $this->assertNotNull($workflow->triggers);
        $this->assertIsArray($workflow->triggers);
    }

    public function test_workflow_has_actions()
    {
        $workflow = Workflow::factory()->create();

        $this->assertNotNull($workflow->actions);
        $this->assertIsArray($workflow->actions);
    }
}
