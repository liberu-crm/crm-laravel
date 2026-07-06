<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\Contracts\CreatesTeams;
use Laravel\Jetstream\Http\Livewire\CreateTeamForm;

class CreateTeam extends CreateTeamForm
{
    #[\Override]
    public function createTeam(CreatesTeams $creator): mixed
    {
        // Validation lives in the CreatesTeams action (validateWithBag('createTeam'));
        // the component defines no rules(), so calling $this->validate() here throws
        // MissingRulesException. Clear stale errors like the parent does instead.
        $this->resetErrorBag();

        $team = $creator->create(
            Auth::user(),
            ['name' => $this->state['name'] ?? null]
        );

        // 'filament.pages.edit-team' is the tenant-profile page's $view string, not a
        // registered route — route() throws RouteNotFoundException. The real route is
        // 'filament.app.tenant.profile' (app/{tenant}/profile), keyed by {tenant}.
        return redirect()->route('filament.app.tenant.profile', ['tenant' => $team]);
    }
}
