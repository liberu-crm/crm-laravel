<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Resolves whether an email is an active portal customer — i.e. a User holding
 * the global (team_id = null) `customer` role. Queried directly so it never
 * mutates the request's permission-team context (safe to call inside Filament
 * action visibility closures during a tenant-scoped request).
 */
class PortalCustomer
{
    public static function forEmail(?string $email): ?User
    {
        if (blank($email)) {
            return null;
        }

        $user = User::where('email', $email)->first();
        if (! $user instanceof User) {
            return null;
        }

        return self::hasGlobalCustomerRole($user) ? $user : null;
    }

    public static function existsForEmail(?string $email): bool
    {
        return self::forEmail($email) instanceof User;
    }

    private static function hasGlobalCustomerRole(User $user): bool
    {
        $tables = config('permission.table_names');
        $morphKey = config('permission.column_names.model_morph_key');
        $teamKey = config('permission.column_names.team_foreign_key');
        $roleKey = app(PermissionRegistrar::class)->pivotRole;

        return DB::table($tables['model_has_roles'])
            ->join($tables['roles'], $tables['roles'].'.id', '=', $tables['model_has_roles'].'.'.$roleKey)
            ->where($tables['model_has_roles'].'.model_type', $user->getMorphClass())
            ->where($tables['model_has_roles'].'.'.$morphKey, $user->getKey())
            ->whereNull($tables['model_has_roles'].'.'.$teamKey)
            ->where($tables['roles'].'.name', Role::Customer->value)
            ->exists();
    }
}
