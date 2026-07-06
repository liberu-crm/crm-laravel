<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Record-level access resolver for RestrictsToOwner models.
 *
 * Returns the user id a query must be restricted to (owner-only visibility),
 * or null for "see everything in scope". Only the named restricted roles are
 * owner-scoped; every other role (manager/admin/super_admin), a roleless user,
 * and non-auth contexts (console, queue) see all records in scope.
 *
 * Resolution: an explicit restrictTo()/clear() override (tests, "run as user"
 * jobs) wins; otherwise the authenticated user is resolved lazily via the
 * sanctum guard then the default guard, so it works on both API and web/panel
 * requests at query-build time.
 */
class AccessContext
{
    /** Roles restricted to records they own; all other roles see everything in scope. */
    private const RESTRICTED_ROLES = ['sales_rep', 'free'];

    protected static ?int $ownerId = null;

    protected static bool $overridden = false;

    public static function restrictTo(?int $userId): void
    {
        static::$ownerId = $userId;
        static::$overridden = true;
    }

    public static function clear(): void
    {
        static::$ownerId = null;
        static::$overridden = false;
    }

    public static function restrictToOwnerId(): ?int
    {
        if (static::$overridden) {
            return static::$ownerId;
        }

        $user = Auth::guard('sanctum')->user() ?? Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        foreach (self::RESTRICTED_ROLES as $role) {
            if ($user->hasRole($role)) {
                return $user->getKey();
            }
        }

        return null;
    }
}
