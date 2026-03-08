<?php

namespace App\Http\Controllers;

use App\Actions\Jetstream\InviteTeamMember;
use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\Jetstream;

class TeamInvitationController extends Controller
{
    public function sendInvitation(Request $request, InviteTeamMember $inviter)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'string'],
            'team_id' => ['required', 'exists:teams,id'],
        ]);

        $team = Team::findOrFail($request->team_id);

        Gate::forUser($request->user())->authorize('addTeamMember', $team);

        $inviter->invite(
            $request->user(),
            $team,
            $request->email,
            $request->role
        );

        return back()->with('success', __('Invitation sent successfully.'));
    }

    public function acceptInvitation(Request $request, $invitationId)
    {
        $invitation = TeamInvitation::findOrFail($invitationId);

        $user = Jetstream::findUserByEmailOrFail($invitation->email);

        abort_if(
            $request->user() && $request->user()->id !== $user->id,
            403,
            __('You are not authorized to accept this invitation.')
        );

        $user->switchTeam($invitation->team);

        $invitation->team->users()->attach(
            $user, ['role' => $invitation->role]
        );

        $invitation->delete();

        return redirect(config('fortify.home'))->with('success', __('You have joined the team!'));
    }
}
