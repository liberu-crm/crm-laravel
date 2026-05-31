<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_with_factory(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', ['email' => $user->email]);
    }

    public function test_user_has_personal_team(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->assertNotNull($user->currentTeam);
        $this->assertTrue($user->currentTeam->personal_team);
    }

    public function test_user_password_is_hidden(): void
    {
        $user = User::factory()->create();

        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_user_has_connected_accounts_relation(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->connectedAccounts());
    }

    public function test_user_has_dashboard_widgets_relation(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->dashboardWidgets());
    }

    public function test_user_email_verification(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->assertFalse($user->hasVerifiedEmail());

        $user->markEmailAsVerified();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_user_casts_are_configured_correctly(): void
    {
        $user = new User;
        $casts = $user->getCasts();

        $this->assertArrayHasKey('password', $casts);
        $this->assertArrayHasKey('email_verified_at', $casts);
    }
}
