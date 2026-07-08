<?php

declare(strict_types=1);

namespace App\Actions\Sso;

use App\Enums\Role;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamManagementService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Just-in-time provisioning of an SSO user into a team (G2 slice 3). The caller
 * (SsoLoginController) has already checked allow_jit + the domain allowlist.
 * Idempotent: reuses an existing user, attaches once, roles once.
 */
class ProvisionSsoUser
{
    public function __construct(private TeamManagementService $teams) {}

    public function __invoke(Team $team, string $email, ?string $name): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name ?: Str::before($email, '@'),
                // Random unusable password — this account signs in via SSO only.
                'password' => Hash::make(Str::random(40)),
                // The IdP verified the address.
                'email_verified_at' => now(),
            ],
        );

        if (! $user->belongsToTeam($team)) {
            $team->users()->attach($user);
        }

        setPermissionsTeamId($team->getKey());
        if ($user->getRoleNames()->isEmpty()) {
            // Least privilege: a JIT user lands as Free, never admin.
            $this->teams->assignTeamRole($user, $team, Role::Free);
        }

        return $user;
    }
}
