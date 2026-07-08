<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\Team;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Spatie\Permission\Models\Role as SpatieRole;

class TeamManagementService
{
    /** Roles a team admin may assign to a member (super_admin is global, customer is portal). */
    public const TEAM_ROLES = [Role::Admin, Role::Manager, Role::SalesRep, Role::Free];

    /**
     * Replace a member's team-scoped role. Removes any of the four team roles the
     * user holds in this team, then assigns the new one — never touching global
     * super_admin/customer. Guards the role so the UI Select can't be bypassed.
     */
    /**
     * Add an existing user to a team with a team role. Attaches membership if
     * needed, then delegates to changeTeamRole (role guard + owner guard + audit).
     */
    public function addTeamMember(User $user, Team $team, Role $role): void
    {
        if (! $user->belongsToTeam($team)) {
            $team->users()->attach($user);
        }

        $this->changeTeamRole($user, $team, $role);
    }

    /**
     * Remove a user from a team: strips their team roles, detaches membership,
     * and audits it. The team owner cannot be removed (mirrors the role guard).
     */
    public function removeTeamMember(User $user, Team $team): void
    {
        if ($user->getKey() === $team->getAttribute('user_id')) {
            throw new InvalidArgumentException('The team owner cannot be removed.');
        }

        setPermissionsTeamId($team->getKey());

        foreach (self::TEAM_ROLES as $existing) {
            if ($user->hasRole($existing->value)) {
                $user->removeRole($existing->value);
            }
        }

        $team->users()->detach($user);

        app(AuditLogService::class)->record(
            'team.member_removed',
            "Removed {$user->getAttribute('email')} from the team",
            $user,
        );
    }

    public function changeTeamRole(User $user, Team $team, Role $role): void
    {
        if (! in_array($role, self::TEAM_ROLES, true)) {
            throw new InvalidArgumentException("Role {$role->value} is not assignable to a team member.");
        }

        // The owner manages the team; their role is immutable here, so a team can
        // never be left without its owner as a stable admin.
        if ($user->getKey() === $team->getAttribute('user_id')) {
            throw new InvalidArgumentException("The team owner's role cannot be changed.");
        }

        setPermissionsTeamId($team->getKey());
        SpatieRole::firstOrCreate(['name' => $role->value, 'guard_name' => 'web', 'team_id' => null]);

        $previous = $user->getRoleNames()->first() ?? 'none';

        foreach (self::TEAM_ROLES as $existing) {
            if ($user->hasRole($existing->value)) {
                $user->removeRole($existing->value);
            }
        }

        $user->assignRole($role->value);

        app(AuditLogService::class)->record(
            'team.role_changed',
            "Changed {$user->getAttribute('email')} from {$previous} to {$role->value}",
            $user,
        );
    }

    /**
     * Assign one of this team's *custom* Spatie roles (team_id = this team) to a
     * member, replacing their current team role. Mirrors changeTeamRole's owner
     * guard + audit, but the role is a per-team custom role instead of a fixed
     * enum role. Removes any fixed team role AND any of this team's custom roles
     * the member already holds, then assigns the new one.
     */
    public function assignCustomRole(User $user, Team $team, SpatieRole $customRole): void
    {
        // Cast: the retrieved role's team_id may come back as a string on some
        // drivers, so compare as ints — a role from another team is not assignable.
        if ((int) $customRole->getAttribute('team_id') !== (int) $team->getKey()) {
            throw new InvalidArgumentException('The role does not belong to this team.');
        }

        // The owner manages the team; their role is immutable here (mirrors changeTeamRole).
        if ($user->getKey() === $team->getAttribute('user_id')) {
            throw new InvalidArgumentException("The team owner's role cannot be changed.");
        }

        setPermissionsTeamId($team->getKey());

        $previous = $user->getRoleNames()->first() ?? 'none';

        // Drop any fixed team role...
        foreach (self::TEAM_ROLES as $existing) {
            if ($user->hasRole($existing->value)) {
                $user->removeRole($existing->value);
            }
        }

        // ...and any of this team's custom roles the member currently holds.
        foreach (SpatieRole::where('team_id', $team->getKey())->pluck('name') as $name) {
            if ($user->hasRole($name)) {
                $user->removeRole($name);
            }
        }

        $newRole = $customRole->getAttribute('name');
        $user->assignRole($newRole);

        app(AuditLogService::class)->record(
            'team.role_changed',
            "Changed {$user->getAttribute('email')} from {$previous} to {$newRole}",
            $user,
        );
    }

    public function createDefaultTeamForUser(User $user): Team
    {
        try {
            $defaultBranch = Branch::firstOrFail();
        } catch (ModelNotFoundException) {
            throw new Exception('No default branch found. Please set up at least one branch.');
        }

        $team = $user->ownedTeams()->create([
            'name' => $defaultBranch->name.' Team',
            'personal_team' => false,
            'branch_id' => $defaultBranch->id,
        ]);

        $this->assignTeamRole($user, $team, Role::Admin);

        return $team;
    }

    public function createPersonalTeamForUser(User $user): Team
    {
        $team = $user->ownedTeams()->create([
            'name' => $user->name."'s Team",
            'personal_team' => true,
        ]);

        $this->assignTeamRole($user, $team, Role::Admin);

        return $team;
    }

    /**
     * Assign a Spatie role to a user scoped to a specific team.
     *
     * The role definition is global (team_id = null); the assignment carries the
     * team. Wrapping setPermissionsTeamId around assignRole is what writes the
     * per-team pivot row. firstOrCreate keeps it safe when roles are not seeded.
     */
    public function assignTeamRole(User $user, Team $team, Role $role): void
    {
        setPermissionsTeamId($team->getKey());
        SpatieRole::firstOrCreate([
            'name' => $role->value,
            'guard_name' => 'web',
            'team_id' => null,
        ]);
        $user->assignRole($role->value);
    }

    public function assignUserToDefaultTeam(User $user): void
    {
        $defaultTeam = Team::where('personal_team', false)->first();

        if (! $defaultTeam) {
            try {
                $defaultTeam = $this->createDefaultTeamForUser($user);
            } catch (\Throwable) {
                // Fallback: create a personal team when no branch/default team exists
                $defaultTeam = $this->createPersonalTeamForUser($user);
                $user->current_team_id = $defaultTeam->id;
                $user->save();

                return;
            }
        }

        $this->assignUserToTeam($user, $defaultTeam);
    }

    public function assignUserToTeam(User $user, Team $team): void
    {
        if (! $user->belongsToTeam($team)) {
            $user->teams()->attach($team, ['role' => 'member']);
            $user->unsetRelation('teams'); // drop cached (pre-attach) relation so switchTeam re-checks membership
            // New member of an existing team → sales_rep in that team. Raw
            // attach() fires no Jetstream event, so assign here.
            $this->assignTeamRole($user, $team, Role::SalesRep);
        }
        $user->switchTeam($team);
    }

    public function switchTeam(User $user, Team $team): void
    {
        if (! $user->belongsToTeam($team)) {
            throw new Exception('User does not belong to the specified team.');
        }
        $user->switchTeam($team);
    }
}
