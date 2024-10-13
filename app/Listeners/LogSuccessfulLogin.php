<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Services\AuditLogService;

class LogSuccessfulLogin
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    public function handle(Login $event): void
    {
        $this->auditLogService->log('login', 'User logged in');
    }
}