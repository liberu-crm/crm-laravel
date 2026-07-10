<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Gates a Filament resource on spatie `{action}_{resource}` permissions
 * (App\Support\PermissionCatalog). Composes with — does not replace — the
 * resource's existing authorization: each override ANDs the permission check
 * with parent::can*(), so team-ownership policies and IsTenantModel scoping
 * still apply.
 *
 * `view` gates list + record read; `delete` gates single + bulk delete.
 * permissionResource() defaults to the snake of the model; override it on
 * resources whose model name doesn't snake-case cleanly.
 *
 * NOTE: we resolve permissions via getAllPermissions()->contains(), NOT
 * $user->can(). Under this app's spatie teams-mode setup, can() routes through
 * hasPermissionViaRole()->hasRole(Collection) which mis-resolves for the global
 * system roles (verified: getAllPermissions lists the permission while can()
 * returns false). getAllPermissions() and hasRole(name) are reliable, and both
 * system-role and custom-role grants feed getAllPermissions(). super_admin is
 * short-circuited by the reliable role-name check (its Gate::before bypass does
 * not reach these resource-level static gates).
 */
trait EnforcesResourcePermissions
{
    public static function permissionResource(): string
    {
        return Str::snake(class_basename(static::getModel()));
    }

    protected static function userHasPermission(string $action): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->getAllPermissions()
            ->contains('name', $action.'_'.static::permissionResource());
    }

    public static function canViewAny(): bool
    {
        return static::userHasPermission('view') && parent::canViewAny();
    }

    public static function canView(Model $record): bool
    {
        return static::userHasPermission('view') && parent::canView($record);
    }

    public static function canCreate(): bool
    {
        return static::userHasPermission('create') && parent::canCreate();
    }

    public static function canEdit(Model $record): bool
    {
        return static::userHasPermission('update') && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return static::userHasPermission('delete') && parent::canDelete($record);
    }

    public static function canDeleteAny(): bool
    {
        return static::userHasPermission('delete') && parent::canDeleteAny();
    }
}
