<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\SamlConnection;
use App\Models\SsoConnection;
use App\Models\Team;
use App\Models\User;

class SsoEnforcement
{
    /**
     * The user's team (owned or member) that mandates SSO — an enabled OIDC or
     * SAML connection with require_sso — or null. Read unscoped: enforcement runs
     * pre-tenant.
     */
    public static function enforcingTeamFor(User $user): ?Team
    {
        $teamIds = $user->allTeams()->pluck('id')->all();

        if ($teamIds === []) {
            return null;
        }

        $oidc = SsoConnection::withoutGlobalScope('tenant')
            ->whereIn('team_id', $teamIds)
            ->where('enabled', true)
            ->where('require_sso', true)
            ->first();

        if ($oidc !== null) {
            return $oidc->team;
        }

        $saml = SamlConnection::withoutGlobalScope('tenant')
            ->whereIn('team_id', $teamIds)
            ->where('enabled', true)
            ->where('require_sso', true)
            ->first();

        return $saml?->team;
    }

    /**
     * The login route to bounce an enforced team to: SAML if it has an enabled
     * SAML connection, else the OIDC redirect. A team uses one protocol.
     */
    public static function loginRouteFor(Team $team): string
    {
        $hasSaml = SamlConnection::withoutGlobalScope('tenant')
            ->where('team_id', $team->getKey())
            ->where('enabled', true)
            ->exists();

        return $hasSaml ? 'saml.login' : 'sso.redirect';
    }
}
