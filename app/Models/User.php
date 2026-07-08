<?php

namespace App\Models;

use App\Enums\Role;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use JoelButcher\Socialstream\HasConnectedAccounts;
use JoelButcher\Socialstream\SetsProfilePhotoFromUrl;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasDefaultTenant, HasTenants
{
    use HasApiTokens;
    use HasConnectedAccounts;
    use HasFactory;
    use HasProfilePhoto {
        HasProfilePhoto::profilePhotoUrl as getPhotoUrl;
    }
    use HasRoles;
    use HasTeams;
    use Notifiable;
    use SetsProfilePhotoFromUrl;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_calendar_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'google_calendar_token',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'google_calendar_token' => 'encrypted',
            'password' => 'hashed',
        ];
    }

    public function dashboardWidgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class);
    }

    public function inAppNotifications(): MorphMany
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')
            ->orderByDesc('created_at');
    }

    public function profilePhotoUrl(): Attribute
    {
        return filter_var($this->profile_photo_path, FILTER_VALIDATE_URL)
            ? Attribute::get(fn () => $this->profile_photo_path)
            : $this->getPhotoUrl();
    }

    public function getTenants(Panel $panel): array|Collection
    {
        // Archived teams are excluded by Team's global 'archived' scope, which
        // filters the ownedTeams/teams relations allTeams() re-queries.
        return $this->allTeams();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($tenant instanceof Team && $tenant->isArchived()) {
            return false;
        }

        return $this->ownsTeam($tenant) || $this->teams->contains($tenant);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'super_admin' => $this->hasRole(Role::SuperAdmin),
            'admin' => $this->hasRole(Role::Admin) || $this->hasRole(Role::SuperAdmin),
            'portal' => $this->hasRole(Role::Customer),
            // app + any future panel: staff only. A customer (external end user)
            // must never reach the staff surfaces, so fence them out explicitly
            // rather than falling through to the permissive default.
            default => ! $this->hasRole(Role::Customer),
        };
    }

    public function canAccessFilament(): bool
    {
        return $this->hasVerifiedEmail()
            && $this->hasAnyRole(Role::values());
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        $team = $this->latestTeam;

        return $team instanceof Team && $team->isArchived() ? null : $team;
    }

    public function latestTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    /**
     * Team-aware role check.
     *
     * Spatie runs in teams mode: a role assignment (model_has_roles) carries the
     * team it applies in. `$this->roles` is the Spatie relation already scoped to
     * the current permission team (see setPermissionsTeamId in the request
     * middleware), so per-team roles (admin/manager/sales_rep/free) resolve for
     * the team the user is acting in.
     *
     * A row with team_id = null is a *global* assignment that applies in every
     * team — this is how super_admin is stored, so hasRole('super_admin') answers
     * true team-independently (regardless of the current setPermissionsTeamId).
     */
    public function hasRole($role, ?string $guard = null): bool
    {
        if (is_array($role)) {
            foreach ($role as $r) {
                if ($this->hasRole($r, $guard)) {
                    return true;
                }
            }

            return false;
        }

        $roleName = $role instanceof Role ? $role->value : strtolower((string) $role);

        if ($this->roles->contains(fn (SpatieRole $r): bool => strtolower($r->name) === $roleName)) {
            return true;
        }

        return $this->hasGlobalRole($roleName);
    }

    /**
     * True when the user holds the named role in a global (team_id = null)
     * assignment, which applies in every team. Queried directly so it ignores
     * the current permission team — this is what makes super_admin platform-wide.
     */
    protected function hasGlobalRole(string $roleName): bool
    {
        if ($this->getKey() === null) {
            return false;
        }

        $tables = config('permission.table_names');
        $morphKey = config('permission.column_names.model_morph_key');
        $teamKey = config('permission.column_names.team_foreign_key');
        $roleKey = app(PermissionRegistrar::class)->pivotRole;

        return DB::table($tables['model_has_roles'])
            ->join($tables['roles'], $tables['roles'].'.id', '=', $tables['model_has_roles'].'.'.$roleKey)
            ->where($tables['model_has_roles'].'.model_type', $this->getMorphClass())
            ->where($tables['model_has_roles'].'.'.$morphKey, $this->getKey())
            ->whereNull($tables['model_has_roles'].'.'.$teamKey)
            ->whereRaw('LOWER('.$tables['roles'].'.name) = ?', [$roleName])
            ->exists();
    }

    /**
     * Territories this user is assigned to (G3 ABAC).
     *
     * @return BelongsToMany<Territory, $this>
     */
    public function territories(): BelongsToMany
    {
        return $this->belongsToMany(Territory::class, 'territory_user');
    }
}
