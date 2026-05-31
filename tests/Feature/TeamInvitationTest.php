<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Jetstream\Mail\TeamInvitation;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_members_can_be_invited_to_team(): void
    {
        Mail::fake();

        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        $this->post('/team-invitations', [
            'email' => 'test@example.com',
            'role' => 'admin',
            'team_id' => $user->currentTeam->id,
        ]);

        Mail::assertSent(TeamInvitation::class);

        $this->assertCount(1, $user->currentTeam->fresh()->teamInvitations);
    }

    public function test_team_member_invitations_can_be_cancelled(): void
    {
        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        $invitation = $user->currentTeam->teamInvitations()->create([
            'email' => 'test@example.com',
            'role' => 'admin',
            'token' => Str::random(40),
        ]);

        $this->delete('/team-invitations/'.$invitation->id);

        $this->assertCount(0, $user->currentTeam->fresh()->teamInvitations);
    }

    public function test_invited_email_address_must_be_a_valid_email(): void
    {
        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        $response = $this->post('/team-invitations', [
            'email' => 'test',
            'role' => 'admin',
            'team_id' => $user->currentTeam->id,
        ]);

        $response->assertSessionHasErrors(['email']);

        $this->assertCount(0, $user->currentTeam->fresh()->teamInvitations);
    }

    public function test_team_member_can_accept_the_invitation(): void
    {
        $team = Team::factory()->create();

        $invitedUser = User::factory()->create();

        $invitation = $team->teamInvitations()->create([
            'email' => $invitedUser->email,
            'role' => 'admin',
            'token' => Str::random(40),
        ]);

        $this->actingAs($invitedUser)->post('/team-invitations/'.$invitation->id.'/accept');

        $this->assertCount(1, $team->fresh()->users);

        $this->assertEquals($invitedUser->id, $team->fresh()->users->first()->id);
    }
}
