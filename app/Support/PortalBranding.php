<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Resolves the customer portal's branding for the authenticated customer's team,
 * falling back to the global config (#489/#519). On the unauthenticated login
 * page there is no customer, so the config default is used.
 */
class PortalBranding
{
    private static function currentTeam(): ?Team
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        $teamId = $user->getAttribute('current_team_id');

        return $teamId ? Team::find($teamId) : null;
    }

    public static function brandName(): string
    {
        $name = self::currentTeam()?->getAttribute('portal_brand_name');

        return (string) ($name ?: config('portal.brand_name'));
    }

    public static function logo(): ?string
    {
        $team = self::currentTeam();

        // Prefer an uploaded file, then an external URL, then the global config.
        $path = $team?->getAttribute('portal_logo_path');
        if ($path) {
            return Storage::disk('public')->url($path);
        }

        return $team?->getAttribute('portal_logo_url') ?: config('portal.logo');
    }
}
