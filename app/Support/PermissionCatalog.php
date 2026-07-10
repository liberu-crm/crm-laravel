<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * Builds the app-panel permission catalog and assigns the system-role matrix
 * from config/permissions.php. Idempotent — both PermissionsSeeder (fresh
 * installs) and the seed-role-permissions migration (existing deploys) call
 * sync(), so it must be safe to run repeatedly and when roles already exist.
 *
 * System roles are global (team_id = null): their permissions apply in every
 * team. Per-request team context for user-level can() checks is set by the
 * TeamsPermission middleware, not here.
 */
class PermissionCatalog
{
    private const GUARD = 'web';

    /**
     * Legacy custom permissions kept for backward compatibility with existing
     * references (reports widget, management gates).
     */
    private const LEGACY_PERMISSIONS = [
        'view_reports', 'manage_users', 'manage_roles', 'manage_permissions',
    ];

    public static function sync(): void
    {
        $registrar = app(PermissionRegistrar::class);
        // Operate on the global (team-less) role/permission definitions.
        $registrar->setPermissionsTeamId(null);

        self::mintPermissions();
        self::assignMatrix();

        $registrar->forgetCachedPermissions();
    }

    /**
     * @return list<string> every `{action}_{resource}` name in the catalog
     */
    public static function catalogPermissions(): array
    {
        $actions = config('permissions.actions', []);
        $names = [];

        foreach (config('permissions.groups', []) as $resources) {
            foreach ($resources as $resource) {
                foreach ($actions as $action) {
                    $names[] = "{$action}_{$resource}";
                }
            }
        }

        return $names;
    }

    private static function mintPermissions(): void
    {
        $wanted = [...self::catalogPermissions(), ...self::LEGACY_PERMISSIONS];
        $existing = Permission::where('guard_name', self::GUARD)
            ->whereIn('name', $wanted)->pluck('name')->all();

        $missing = array_diff($wanted, $existing);
        if ($missing === []) {
            return;
        }

        // Bulk insert keeps repeated seeding (every RefreshDatabase test) cheap.
        $now = now();
        Permission::insertOrIgnore(array_map(
            fn (string $name): array => [
                'name' => $name,
                'guard_name' => self::GUARD,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            array_values($missing),
        ));
    }

    private static function assignMatrix(): void
    {
        foreach (Role::cases() as $roleEnum) {
            $role = SpatieRole::firstOrCreate([
                'name' => $roleEnum->value,
                'guard_name' => self::GUARD,
                'team_id' => null,
            ]);

            if ($roleEnum === Role::SuperAdmin) {
                // Omnipotent — also covered by shield's Gate::before bypass.
                $role->syncPermissions(Permission::where('guard_name', self::GUARD)->get());

                continue;
            }

            $role->syncPermissions(self::permissionsForRole($roleEnum->value));
        }
    }

    /**
     * @return list<string>
     */
    private static function permissionsForRole(string $role): array
    {
        $config = config("permissions.matrix.{$role}");

        if (! is_array($config)) {
            return []; // customer + any unlisted role → no app-panel permissions
        }

        $groups = config('permissions.groups', []);
        $names = [];

        foreach ($config['groups'] ?? [] as $group => $actions) {
            foreach ($groups[$group] ?? [] as $resource) {
                foreach ($actions as $action) {
                    $names[] = "{$action}_{$resource}";
                }
            }
        }

        foreach ($config['resources'] ?? [] as $resource => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$action}_{$resource}";
            }
        }

        return array_values(array_unique($names));
    }
}
