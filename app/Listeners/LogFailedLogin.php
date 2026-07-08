<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        // Unknown email => no user to attribute; audit_logs.user_id is NOT NULL,
        // and an unattributable probe has no audit value. Skip it.
        if ($event->user === null) {
            return;
        }

        AuditLog::create([
            'user_id' => $event->user->getAuthIdentifier(),
            'action' => 'auth.failed',
            'description' => 'Failed login attempt',
            'ip_address' => request()->ip() ?? '0.0.0.0',
        ]);
    }
}
