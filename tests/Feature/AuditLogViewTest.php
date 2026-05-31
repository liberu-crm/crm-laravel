<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_audit_logs(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($role);

        $response = $this->actingAs($admin)->get('/admin/audit-logs');

        $response->assertSuccessful();
    }

    public function test_non_admin_cannot_view_audit_logs(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/audit-logs');

        $response->assertForbidden();
    }
}
