<?php

declare(strict_types=1);

namespace Tests\Feature\Sso;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SsoLogoutAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_sso_logout_is_audited_when_the_session_was_sso_established(): void
    {
        $user = User::factory()->create();

        // SsoLoginController stamps this flag on a successful SSO login.
        $this->actingAs($user);
        session(['sso_authenticated' => true]);

        Auth::logout();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.sso_logout',
            'user_id' => $user->id,
        ]);
    }

    public function test_non_sso_logout_is_not_audited_as_sso(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Auth::logout();

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'auth.sso_logout',
        ]);
    }
}
