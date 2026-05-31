<?php

namespace App\Listeners;

use App\Services\AuditLogService;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function __construct(protected \App\Services\AuditLogService $auditLogService)
    {
    }

    public function handle(Login $event): void
    {
        $this->auditLogService->log('login', 'User logged in');
    }
}
