<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthEventAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_login_against_known_user_is_audited(): void
    {
        $user = User::factory()->create();

        event(new Failed('web', $user, ['email' => $user->email, 'password' => 'x']));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.failed',
            'user_id' => $user->id,
        ]);
    }

    public function test_failed_login_against_unknown_email_is_not_audited(): void
    {
        event(new Failed('web', null, ['email' => 'nobody@example.com', 'password' => 'x']));

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'auth.failed',
        ]);
    }

    public function test_password_reset_is_audited(): void
    {
        $user = User::factory()->create();

        event(new PasswordReset($user));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.password_reset',
            'user_id' => $user->id,
        ]);
    }
}
