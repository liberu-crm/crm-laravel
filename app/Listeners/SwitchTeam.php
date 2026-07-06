<?php

declare(strict_types=1);

namespace App\Listeners;

use Filament\Events\TenantSet;

class SwitchTeam
{
    /**
     * When the app panel resolves its tenant, scope Spatie permissions to that
     * team so per-team roles resolve for the team the user is actually viewing
     * (the authoritative acting team on the panel, which may differ from the
     * pre-request current_team_id the web middleware used).
     */
    public function handle(object $event): void
    {
        if ($event instanceof TenantSet) {
            setPermissionsTeamId($event->getTenant()->getKey());
        }
    }
}
