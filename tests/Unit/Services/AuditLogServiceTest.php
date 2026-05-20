<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $auditLogService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditLogService = new AuditLogService();
    }

    public function test_it_creates_audit_log()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->auditLogService->log('test_action', 'Test description');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'test_action',
            'description' => 'Test description',
        ]);
    }
}