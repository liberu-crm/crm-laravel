<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Task;
use App\Models\Workflow;
use App\Models\WorkflowAction;
use App\Models\WorkflowCondition;
use App\Models\WorkflowExecution;
use App\Models\WorkflowTrigger;
use App\Services\WorkflowAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowAutomationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): WorkflowAutomationService
    {
        return app(WorkflowAutomationService::class);
    }

    /** update_contact is a schema-safe, observable action (maps to real Contact columns). */
    private function updateContactAction(Workflow $workflow, string $status = 'converted'): WorkflowAction
    {
        return WorkflowAction::create([
            'workflow_id' => $workflow->id,
            'type' => WorkflowAction::TYPE_UPDATE_CONTACT,
            'name' => 'Set status',
            'config' => ['fields' => ['status' => $status]],
            'order' => 1,
            'is_active' => true,
        ]);
    }

    public function test_execute_workflow_runs_actions_and_records_completed_execution(): void
    {
        $contact = Contact::factory()->create(['status' => 'lead']);
        $workflow = Workflow::factory()->create(['is_active' => true]);
        $this->updateContactAction($workflow);

        $execution = $this->service()->executeWorkflow($workflow, $contact);

        $this->assertInstanceOf(WorkflowExecution::class, $execution);
        $this->assertSame(WorkflowExecution::STATUS_COMPLETED, $execution->status);
        $this->assertSame($workflow->id, $execution->workflow_id);
        $this->assertSame(Contact::class, $execution->entity_type);
        $this->assertSame($contact->id, $execution->entity_id);
        // execution is reachable via the workflow relation
        $this->assertTrue($workflow->executions()->whereKey($execution->id)->exists());
        // the action actually ran
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'status' => 'converted']);
    }

    public function test_create_task_action_creates_task_for_contact_and_completes(): void
    {
        $contact = Contact::factory()->create();
        $workflow = Workflow::factory()->create(['is_active' => true]);
        WorkflowAction::create([
            'workflow_id' => $workflow->id,
            'type' => WorkflowAction::TYPE_CREATE_TASK,
            'name' => 'Follow up',
            'config' => ['title' => 'Follow up with contact'],
            'order' => 1,
            'is_active' => true,
        ]);

        $execution = $this->service()->executeWorkflow($workflow, $contact);

        // action ran without throwing => execution completed, not failed
        $this->assertSame(WorkflowExecution::STATUS_COMPLETED, $execution->status);
        $this->assertNull($execution->error_message);

        $task = Task::query()->where('contact_id', $contact->id)->first();
        $this->assertNotNull($task);
        $this->assertSame('Follow up with contact', $task->name);
        $this->assertSame($contact->id, $task->contact_id);
        $this->assertNotNull($task->due_date);
    }

    public function test_trigger_executes_workflow_with_matching_trigger(): void
    {
        $contact = Contact::factory()->create(['status' => 'lead']);
        $workflow = Workflow::factory()->create(['is_active' => true]);
        WorkflowTrigger::create([
            'workflow_id' => $workflow->id,
            'type' => WorkflowTrigger::TYPE_CONTACT_CREATED,
            'is_active' => true,
        ]);
        $this->updateContactAction($workflow);

        $this->service()->trigger(WorkflowTrigger::TYPE_CONTACT_CREATED, $contact);

        $this->assertSame(1, WorkflowExecution::count());
        $this->assertSame(WorkflowExecution::STATUS_COMPLETED, WorkflowExecution::first()->status);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'status' => 'converted']);
    }

    public function test_trigger_ignores_non_matching_trigger(): void
    {
        $contact = Contact::factory()->create();
        $workflow = Workflow::factory()->create(['is_active' => true]);
        WorkflowTrigger::create([
            'workflow_id' => $workflow->id,
            'type' => WorkflowTrigger::TYPE_CONTACT_CREATED,
            'is_active' => true,
        ]);

        $this->service()->trigger(WorkflowTrigger::TYPE_DEAL_CREATED, $contact);

        $this->assertSame(0, WorkflowExecution::count());
    }

    public function test_condition_blocks_action_when_not_met(): void
    {
        $contact = Contact::factory()->create(['status' => 'lead']);
        $workflow = Workflow::factory()->create(['is_active' => true]);
        $action = $this->updateContactAction($workflow);
        WorkflowCondition::create([
            'workflow_action_id' => $action->id,
            'field' => 'status',
            'operator' => WorkflowCondition::OPERATOR_EQUALS,
            'value' => 'vip', // contact status is 'lead' => condition false
            'logical_operator' => 'and',
        ]);

        $execution = $this->service()->executeWorkflow($workflow, $contact);

        // execution completes, but the gated action did NOT run
        $this->assertSame(WorkflowExecution::STATUS_COMPLETED, $execution->status);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'status' => 'lead']);
    }

    public function test_condition_allows_action_when_met(): void
    {
        $contact = Contact::factory()->create(['status' => 'lead']);
        $workflow = Workflow::factory()->create(['is_active' => true]);
        $action = $this->updateContactAction($workflow);
        WorkflowCondition::create([
            'workflow_action_id' => $action->id,
            'field' => 'status',
            'operator' => WorkflowCondition::OPERATOR_EQUALS,
            'value' => 'lead', // matches => condition true
            'logical_operator' => 'and',
        ]);

        $this->service()->executeWorkflow($workflow, $contact);

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'status' => 'converted']);
    }
}
