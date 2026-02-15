# Workflow Automation Guide

## Overview

The enhanced workflow automation system enables you to create sophisticated, multi-step automations with conditional logic, delays, and various action types.

## Key Features

- **Visual Workflow Builder** - Drag-and-drop interface for creating workflows
- **Multiple Trigger Types** - Start workflows based on various events
- **Conditional Actions** - Execute actions based on specific conditions
- **Time Delays** - Schedule actions for future execution
- **Complex Logic** - AND/OR conditions for fine-grained control
- **Execution Tracking** - Monitor workflow runs and debug issues

## Workflow Components

### 1. Triggers

Workflows start when specific events occur:

#### Contact Triggers
- `contact_created` - When a new contact is added
- `contact_updated` - When contact details change
- `contact_property_changed` - When a specific field changes

#### Deal Triggers
- `deal_created` - When a new deal is created
- `deal_stage_changed` - When deal moves to different stage

#### Engagement Triggers
- `email_opened` - When a contact opens an email
- `email_clicked` - When a contact clicks a link
- `form_submitted` - When a form is submitted
- `page_viewed` - When a specific page is viewed

#### Task Triggers
- `task_completed` - When a task is marked complete

#### Time-based Triggers
- `date_property` - Based on a date field (e.g., birthday)
- `schedule` - Run on specific schedule (daily, weekly)

### 2. Actions

Actions are executed when workflow conditions are met:

#### Communication Actions
- `send_email` - Send templated or custom email
- `send_sms` - Send SMS via Twilio

#### Data Actions
- `update_contact` - Modify contact fields
- `create_task` - Create follow-up task
- `create_deal` - Create sales opportunity
- `add_tag` - Add tag to contact
- `remove_tag` - Remove tag from contact
- `change_stage` - Move to different stage

#### Assignment Actions
- `assign_to_user` - Assign to team member
- `add_to_list` - Add to marketing list
- `remove_from_list` - Remove from list

#### Integration Actions
- `webhook` - Call external API
- `wait` - Add time delay

#### Conditional Actions
- `if_then` - Execute based on conditions

### 3. Conditions

Control when actions execute:

#### Operators
- `equals` - Exact match
- `not_equals` - Does not match
- `contains` - Contains text
- `not_contains` - Does not contain
- `greater_than` - Numeric comparison
- `less_than` - Numeric comparison
- `is_set` - Field has value
- `is_not_set` - Field is empty
- `in_list` - Value in list
- `not_in_list` - Value not in list

#### Logical Operators
- `and` - All conditions must be true
- `or` - Any condition can be true

## Creating Workflows

### Example 1: Welcome Email Sequence

```php
use App\Models\Workflow;
use App\Models\WorkflowTrigger;
use App\Models\WorkflowAction;

// Create workflow
$workflow = Workflow::create([
    'name' => 'Welcome Email Sequence',
    'description' => 'Send welcome emails to new contacts',
    'is_active' => true,
]);

// Add trigger
WorkflowTrigger::create([
    'workflow_id' => $workflow->id,
    'type' => 'contact_created',
    'is_active' => true,
]);

// Action 1: Immediate welcome email
WorkflowAction::create([
    'workflow_id' => $workflow->id,
    'type' => 'send_email',
    'name' => 'Send Welcome Email',
    'config' => [
        'template_id' => 1,
        'subject' => 'Welcome to {{company_name}}!',
    ],
    'order' => 1,
]);

// Action 2: Follow-up after 3 days
WorkflowAction::create([
    'workflow_id' => $workflow->id,
    'type' => 'send_email',
    'name' => 'Send Follow-up Email',
    'config' => [
        'template_id' => 2,
        'subject' => 'Getting started guide',
    ],
    'order' => 2,
    'delay_amount' => 3,
    'delay_unit' => 'days',
]);

// Action 3: Create follow-up task
WorkflowAction::create([
    'workflow_id' => $workflow->id,
    'type' => 'create_task',
    'name' => 'Create Follow-up Task',
    'config' => [
        'title' => 'Check in with {{first_name}}',
        'description' => 'Personal follow-up call',
        'due_date' => '+7 days',
    ],
    'order' => 3,
    'delay_amount' => 7,
    'delay_unit' => 'days',
]);
```

### Example 2: Lead Scoring & Assignment

```php
// Create workflow
$workflow = Workflow::create([
    'name' => 'High-Value Lead Assignment',
    'description' => 'Automatically assign high-value leads to sales team',
    'is_active' => true,
]);

// Trigger: Email clicked
WorkflowTrigger::create([
    'workflow_id' => $workflow->id,
    'type' => 'email_clicked',
    'is_active' => true,
]);

// Action: Check if high-value and assign
$action = WorkflowAction::create([
    'workflow_id' => $workflow->id,
    'type' => 'assign_to_user',
    'name' => 'Assign to Senior Sales Rep',
    'config' => [
        'user_id' => 5, // Senior sales rep
    ],
    'order' => 1,
]);

// Condition: Only if company size > 100
WorkflowCondition::create([
    'workflow_action_id' => $action->id,
    'field' => 'company.employee_count',
    'operator' => 'greater_than',
    'value' => 100,
]);
```

### Example 3: Deal Stage Automation

```php
$workflow = Workflow::create([
    'name' => 'Won Deal Celebration',
    'is_active' => true,
]);

WorkflowTrigger::create([
    'workflow_id' => $workflow->id,
    'type' => 'deal_stage_changed',
    'config' => [
        'stage_name' => 'Won',
    ],
]);

// Send congratulations email
WorkflowAction::create([
    'workflow_id' => $workflow->id,
    'type' => 'send_email',
    'name' => 'Send Success Email',
    'config' => [
        'template_id' => 10,
    ],
    'order' => 1,
]);

// Create onboarding task
WorkflowAction::create([
    'workflow_id' => $workflow->id,
    'type' => 'create_task',
    'name' => 'Schedule Onboarding',
    'config' => [
        'title' => 'Onboarding call for {{company_name}}',
        'due_date' => '+2 days',
    ],
    'order' => 2,
]);

// Webhook to accounting system
WorkflowAction::create([
    'workflow_id' => $workflow->id,
    'type' => 'webhook',
    'name' => 'Notify Accounting',
    'config' => [
        'url' => 'https://accounting.example.com/api/new-customer',
        'method' => 'POST',
    ],
    'order' => 3,
]);
```

## Programmatic Usage

### Triggering Workflows

```php
use App\Services\WorkflowAutomationService;

$automationService = app(WorkflowAutomationService::class);

// Trigger based on event
$automationService->trigger('contact_created', $contact);

// Trigger with additional context
$automationService->trigger('email_clicked', $contact, [
    'email_id' => $email->id,
    'link_url' => 'https://example.com/pricing',
]);
```

### Executing Specific Workflow

```php
use App\Models\Workflow;

$workflow = Workflow::find(1);
$execution = $automationService->executeWorkflow($workflow, $contact);

// Check execution status
if ($execution->status === 'completed') {
    echo "Workflow completed successfully";
} elseif ($execution->status === 'failed') {
    echo "Error: " . $execution->error_message;
}
```

## Monitoring & Debugging

### Viewing Workflow Executions

```php
use App\Models\WorkflowExecution;

// Get all executions for a workflow
$executions = WorkflowExecution::where('workflow_id', 1)
    ->orderBy('created_at', 'desc')
    ->get();

// Get failed executions
$failedExecutions = WorkflowExecution::where('status', 'failed')
    ->with('workflow')
    ->get();

foreach ($failedExecutions as $execution) {
    echo "Workflow: {$execution->workflow->name}\n";
    echo "Error: {$execution->error_message}\n";
    echo "Failed at: {$execution->failed_at}\n";
}
```

### Execution Metadata

Each execution stores metadata about its run:

```php
$execution->metadata = [
    'trigger_event' => 'contact_created',
    'actions_executed' => 3,
    'actions_skipped' => 1,
    'emails_sent' => 1,
    'tasks_created' => 1,
];
```

## Best Practices

### 1. Test Before Activating

Create workflows in inactive state and test thoroughly:

```php
$workflow->update(['is_active' => false]);

// Test with sample data
$automationService->executeWorkflow($workflow, $testContact);

// Activate when ready
$workflow->update(['is_active' => true]);
```

### 2. Use Appropriate Delays

Don't overwhelm contacts with immediate actions:

```php
// Bad: All actions fire immediately
$action1->delay_amount = 0;
$action2->delay_amount = 0;
$action3->delay_amount = 0;

// Good: Spread out over time
$action1->delay_amount = 0;     // Immediate
$action2->delay_amount = 1;     // 1 day later
$action3->delay_amount = 3;     // 3 days later
```

### 3. Implement Error Handling

Always have fallback options:

```php
// Action with conditions
$action = WorkflowAction::create([
    'type' => 'assign_to_user',
    'config' => [
        'user_id' => 5,
        'fallback_user_id' => 1, // Default assignee
    ],
]);
```

### 4. Monitor Performance

Track workflow effectiveness:

```php
$workflow->metadata = [
    'success_rate' => 95.5,
    'average_completion_time' => 45, // minutes
    'total_executions' => 1500,
    'failed_executions' => 23,
];
```

### 5. Document Your Workflows

Add clear descriptions:

```php
$workflow->description = 
    "Sends welcome series to new contacts:\n" .
    "1. Immediate welcome email\n" .
    "2. Follow-up after 3 days\n" .
    "3. Check-in task after 7 days";
```

## Advanced Features

### Complex Conditions

Combine multiple conditions with AND/OR logic:

```php
// Send only to engaged, high-value contacts
$action = WorkflowAction::create([...]);

WorkflowCondition::create([
    'workflow_action_id' => $action->id,
    'field' => 'metadata.email_engagement.score',
    'operator' => 'greater_than',
    'value' => 50,
    'logical_operator' => 'and',
]);

WorkflowCondition::create([
    'workflow_action_id' => $action->id,
    'field' => 'company.annual_revenue',
    'operator' => 'greater_than',
    'value' => 1000000,
    'logical_operator' => 'and',
]);
```

### Dynamic Content

Use workflow context in actions:

```php
WorkflowAction::create([
    'type' => 'send_email',
    'config' => [
        'subject' => 'Thanks for clicking {{link_url}}',
        'body' => 'Hi {{first_name}}, we noticed you clicked...',
    ],
]);
```

## Troubleshooting

### Workflow Not Triggering

1. Check if workflow is active
2. Verify trigger configuration
3. Ensure event is firing correctly

### Actions Not Executing

1. Check action conditions
2. Verify entity has required fields
3. Review execution logs

### Delays Not Working

Delays require a queue system. Ensure:

```bash
php artisan queue:work
```

is running for scheduled actions.
