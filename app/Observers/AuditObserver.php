<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Records created/updated/deleted events for tenant models into AuditLog.
 *
 * Recursion is prevented by registration, not a guard: AuditObserver is only
 * attached to the core tenant models in AppServiceProvider, never to AuditLog,
 * so writing an audit row fires no further events.
 */
class AuditObserver
{
    public function created(Model $model): void
    {
        $this->record($model, 'created', $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->record($model, 'updated', $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted', null);
    }

    /**
     * @param  array<string, mixed>|null  $changes
     */
    private function record(Model $model, string $action, ?array $changes): void
    {
        // audit_logs.user_id is NOT NULL + FK; nothing to attribute unauthenticated
        // writes to (seeders, console, queue), so skip rather than blow the constraint.
        if (! auth()->check()) {
            return;
        }

        $description = $model::class.'#'.$model->getKey().' '.$action;

        // Legacy description behaviour: only the update event appended the diff.
        if ($action === 'updated' && $changes) {
            $description .= ' '.json_encode($changes);
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'team_id' => $model->getAttribute('team_id'),
            'changes' => $changes,
        ]);
    }
}
