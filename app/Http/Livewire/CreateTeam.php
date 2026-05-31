<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\Contracts\CreatesTeams;
use Laravel\Jetstream\Http\Livewire\CreateTeamForm;

class CreateTeam extends CreateTeamForm
{
    #[\Override]
    public function createTeam(CreatesTeams $creator)
    {
        $this->validate();

        $team = $creator->create(
            Auth::user(),
            ['name' => $this->state['name']]
        );

        return redirect()->route('filament.pages.edit-team', ['team' => $team]);
    }
}
