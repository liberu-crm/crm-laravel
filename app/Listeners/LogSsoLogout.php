<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\AuditLogService;
use Illuminate\Auth\Events\Logout;

class LogSsoLogout
{
    public function __construct(protected AuditLogService $auditLogService) {}

    public function handle(Logout $event): void
    {
        // Only audit when the session was SSO-established (flag set on SSO login).
        // pull() read-and-clears so the flag can't outlive the session.
        if (! session()->pull('sso_authenticated')) {
            return;
        }

        // Logout can fire with a null user; nothing to attribute then.
        if ($event->user === null) {
            return;
        }

        // log() attributes via Auth::id(), still set here — SessionGuard fires
        // Logout before nulling its user.
        $this->auditLogService->log('auth.sso_logout', 'SSO user logged out');
    }
}
