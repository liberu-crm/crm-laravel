<?php

namespace App\Services;

use App\Models\Workflow;
use App\Models\WorkflowAction;
use App\Models\WorkflowExecution;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Task;
use App\Models\Email;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WorkflowAutomationService
{
    protected $emailService;
    protected $twilioService;

    public function __construct()
    {
        $this->emailService = app(EmailTrackingService::class);
        $this->twilioService = app(TwilioService::class);
    }

    /**
     * Trigger workflow based on event
     */
    public function trigger(string $triggerType, $entity, array $context = []): void
    {
        $workflows = Workflow::whereHas('triggers', function ($query) use ($triggerType) {
            $query->where('type', $triggerType)
                  ->where('is_active', true);
        })->where('is_active', true)->get();

        foreach ($workflows as $workflow) {
            $this->executeWorkflow($workflow, $entity, $context);
        }
    }

    /**
     * Execute a workflow
     */
    public function executeWorkflow(Workflow $workflow, $entity, array $context = []): WorkflowExecution
    {
        $execution = WorkflowExecution::create([
            'workflow_id' => $workflow->id,
            'entity_type' => get_class($entity),
            'entity_id' => $entity->id,
            'status' => WorkflowExecution::STATUS_PENDING,
            'metadata' => $context,
        ]);

        try {
            $execution->update([
                'status' => WorkflowExecution::STATUS_RUNNING,
                'started_at' => now(),
            ]);

            $actions = $workflow->actions()->orderBy('order')->get();
            
            foreach ($actions as $action) {
                if (!$action->is_active) {
                    continue;
                }

                // Check conditions if any
                if ($action->conditions()->count() > 0) {
                    if (!$this->evaluateConditions($action, $entity)) {
                        continue;
                    }
                }

                // Apply delay if specified
                if ($action->delay_amount > 0) {
                    // In a real implementation, this would schedule the action for later
                    // For now, we'll just log it
                    Log::info("Action {$action->id} would be delayed by {$action->delay_amount} {$action->delay_unit}");
                }

                // Execute the action
                $this->executeAction($action, $entity, $context);
            }

            $execution->update([
                'status' => WorkflowExecution::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            $execution->update([
                'status' => WorkflowExecution::STATUS_FAILED,
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            Log::error("Workflow execution failed: " . $e->getMessage(), [
                'workflow_id' => $workflow->id,
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id,
            ]);
        }

        return $execution;
    }

    /**
     * Evaluate conditions for an action
     */
    protected function evaluateConditions(WorkflowAction $action, $entity): bool
    {
        $conditions = $action->conditions;
        
        if ($conditions->isEmpty()) {
            return true;
        }

        $results = [];
        foreach ($conditions as $condition) {
            $results[] = [
                'result' => $condition->evaluate($entity),
                'operator' => $condition->logical_operator ?? 'and',
            ];
        }

        // Evaluate conditions based on logical operators
        $finalResult = $results[0]['result'];
        for ($i = 1; $i < count($results); $i++) {
            if ($results[$i - 1]['operator'] === 'and') {
                $finalResult = $finalResult && $results[$i]['result'];
            } else {
                $finalResult = $finalResult || $results[$i]['result'];
            }
        }

        return $finalResult;
    }

    /**
     * Execute a single action
     */
    protected function executeAction(WorkflowAction $action, $entity, array $context): void
    {
        $config = $action->config ?? [];

        switch ($action->type) {
            case WorkflowAction::TYPE_SEND_EMAIL:
                $this->sendEmail($entity, $config);
                break;

            case WorkflowAction::TYPE_UPDATE_CONTACT:
                $this->updateContact($entity, $config);
                break;

            case WorkflowAction::TYPE_CREATE_TASK:
                $this->createTask($entity, $config);
                break;

            case WorkflowAction::TYPE_ADD_TAG:
                $this->addTag($entity, $config);
                break;

            case WorkflowAction::TYPE_REMOVE_TAG:
                $this->removeTag($entity, $config);
                break;

            case WorkflowAction::TYPE_CHANGE_STAGE:
                $this->changeStage($entity, $config);
                break;

            case WorkflowAction::TYPE_SEND_SMS:
                $this->sendSms($entity, $config);
                break;

            case WorkflowAction::TYPE_CREATE_DEAL:
                $this->createDeal($entity, $config);
                break;

            case WorkflowAction::TYPE_WEBHOOK:
                $this->callWebhook($entity, $config, $context);
                break;

            case WorkflowAction::TYPE_ASSIGN_TO_USER:
                $this->assignToUser($entity, $config);
                break;

            case WorkflowAction::TYPE_ADD_TO_LIST:
                $this->addToList($entity, $config);
                break;

            case WorkflowAction::TYPE_REMOVE_FROM_LIST:
                $this->removeFromList($entity, $config);
                break;

            default:
                Log::warning("Unknown workflow action type: {$action->type}");
        }
    }

    protected function sendEmail($entity, array $config): void
    {
        if (!$entity instanceof Contact) {
            return;
        }

        // Implementation would use EmailTrackingService
        Log::info("Sending email to contact {$entity->id}");
    }

    protected function updateContact($entity, array $config): void
    {
        if (!$entity instanceof Contact) {
            return;
        }

        $entity->update($config['fields'] ?? []);
    }

    protected function createTask($entity, array $config): void
    {
        Task::create([
            'title' => $config['title'] ?? 'Workflow Task',
            'description' => $config['description'] ?? '',
            'due_date' => $config['due_date'] ?? null,
            'taskable_type' => get_class($entity),
            'taskable_id' => $entity->id,
        ]);
    }

    protected function addTag($entity, array $config): void
    {
        if (!method_exists($entity, 'tags')) {
            return;
        }

        $tags = $config['tags'] ?? [];
        foreach ($tags as $tag) {
            $entity->tags()->attach($tag);
        }
    }

    protected function removeTag($entity, array $config): void
    {
        if (!method_exists($entity, 'tags')) {
            return;
        }

        $tags = $config['tags'] ?? [];
        foreach ($tags as $tag) {
            $entity->tags()->detach($tag);
        }
    }

    protected function changeStage($entity, array $config): void
    {
        if (!$entity instanceof Deal && !$entity instanceof Contact) {
            return;
        }

        if (isset($config['stage_id'])) {
            $entity->update(['stage_id' => $config['stage_id']]);
        }
    }

    protected function sendSms($entity, array $config): void
    {
        if (!$entity instanceof Contact) {
            return;
        }

        if (empty($entity->phone)) {
            return;
        }

        // Implementation would use TwilioService
        Log::info("Sending SMS to contact {$entity->id}");
    }

    protected function createDeal($entity, array $config): void
    {
        if (!$entity instanceof Contact) {
            return;
        }

        Deal::create([
            'title' => $config['title'] ?? 'Workflow Deal',
            'value' => $config['value'] ?? 0,
            'contact_id' => $entity->id,
            'stage_id' => $config['stage_id'] ?? null,
            'user_id' => $config['user_id'] ?? null,
        ]);
    }

    protected function callWebhook($entity, array $config, array $context): void
    {
        $url = $config['url'] ?? null;
        if (!$url) {
            return;
        }

        $method = strtoupper($config['method'] ?? 'POST');
        $data = array_merge($entity->toArray(), $context);

        Http::send($method, $url, ['json' => $data]);
    }

    protected function assignToUser($entity, array $config): void
    {
        if (!isset($config['user_id'])) {
            return;
        }

        $entity->update(['user_id' => $config['user_id']]);
    }

    protected function addToList($entity, array $config): void
    {
        // Implementation for adding to marketing list
        Log::info("Adding entity to list: " . ($config['list_id'] ?? 'unknown'));
    }

    protected function removeFromList($entity, array $config): void
    {
        // Implementation for removing from marketing list
        Log::info("Removing entity from list: " . ($config['list_id'] ?? 'unknown'));
    }
}
