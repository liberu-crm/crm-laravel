<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\SsoConnection;
use App\Models\Team;
use App\Models\User;

class SsoEnforcement
{
    /**
     * The user's team (owned or member) that mandates SSO — an enabled connection
     * with require_sso — or null. Read unscoped: enforcement runs pre-tenant.
     */
    public static function enforcingTeamFor(User $user): ?Team
    {
        $teamIds = $user->allTeams()->pluck('id')->all();

        if ($teamIds === []) {
            return null;
        }

        $connection = SsoConnection::withoutGlobalScope('tenant')
            ->whereIn('team_id', $teamIds)
            ->where('enabled', true)
            ->where('require_sso', true)
            ->first();

        return $connection?->team;
    }
}
