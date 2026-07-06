<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\AuditLogService;
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
    public function __construct(private readonly AuditLogService $audit) {}

    public function created(Model $model): void
    {
        $this->record($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->record($model, 'updated', $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted');
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function record(Model $model, string $action, array $changes = []): void
    {
        // audit_logs.user_id is NOT NULL + FK; nothing to attribute unauthenticated
        // writes to (seeders, console, queue), so skip rather than blow the constraint.
        if (! auth()->check()) {
            return;
        }

        $description = $model::class.'#'.$model->getKey().' '.$action;

        if ($changes !== []) {
            $description .= ' '.json_encode($changes);
        }

        // Reuse the existing service: it stamps user_id + ip_address for us.
        $this->audit->log($action, $description);
    }
}
