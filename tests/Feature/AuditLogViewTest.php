<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_audit_logs()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        AuditLog::factory()->count(5)->create();

        $response = $this->actingAs($admin)->get('/admin/audit-logs');

        $response->assertStatus(200);
        $response->assertSee('Audit Logs');
        $response->assertSee(AuditLog::first()->action);
    }

    public function test_non_admin_cannot_view_audit_logs()
    {
        $user = User::factory()->create(['is_admin' => false]);
        AuditLog::factory()->count(5)->create();

        $response = $this->actingAs($user)->get('/admin/audit-logs');

        $response->assertStatus(403);
    }
}