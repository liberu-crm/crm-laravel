<?php

namespace Tests\Unit\Listeners;

use App\Listeners\LogSuccessfulLogin;
use App\Services\AuditLogService;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class LogSuccessfulLoginTest extends TestCase
{
    use RefreshDatabase;

    public function testHandleMethodLogsSuccessfulLogin()
    {
        // Mock the AuditLogService
        $auditLogService = Mockery::mock(AuditLogService::class);
        $auditLogService->shouldReceive('log')
            ->once()
            ->with('login', 'User logged in');

        // Create an instance of LogSuccessfulLogin with the mocked AuditLogService
        $listener = new LogSuccessfulLogin($auditLogService);

        // Create a mock Login event
        $event = Mockery::mock(Login::class);

        // Call the handle method
        $listener->handle($event);

        // Mockery will automatically verify that the expected method was called
    }
}