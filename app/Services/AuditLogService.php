<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function log($action, $description): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Record an attributable action against an optional subject. Skipped when
     * there is no authenticated actor (audit_logs.user_id is NOT NULL and an
     * unattributable entry has no compliance value). team_id is stamped by
     * AuditLog's IsTenantModel hook from the active tenant.
     */
    public function record(string $action, string $description, ?Model $auditable = null): void
    {
        if (! Auth::check()) {
            return;
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip() ?? '0.0.0.0',
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
        ]);
    }
}
