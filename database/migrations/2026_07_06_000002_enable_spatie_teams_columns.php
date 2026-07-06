<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F4 per-team RBAC: enable Spatie teams columns on databases that already ran
 * create_permission_tables while teams mode was OFF (no team_id on roles /
 * model_has_roles / model_has_permissions).
 *
 * On a fresh migrate, create_permission_tables already adds these columns
 * (teams => true), so every block here is a no-op — guarded by Schema::hasColumn.
 * The pivot composite keys become UNIQUE indexes (not PRIMARY KEYs) because the
 * team_id is nullable — a global super_admin assignment is stored with
 * team_id = null, and MySQL forbids NULL in a PRIMARY KEY.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! config('permission.teams')) {
            return;
        }

        $names = config('permission.table_names');
        $columns = config('permission.column_names');
        $teamKey = $columns['team_foreign_key'] ?? 'team_id';
        $morphKey = $columns['model_morph_key'] ?? 'model_id';
        $roleKey = $columns['role_pivot_key'] ?? 'role_id';
        $permissionKey = $columns['permission_pivot_key'] ?? 'permission_id';

        // roles: add nullable team_id + team-aware unique (team_id, name, guard).
        if (Schema::hasTable($names['roles']) && ! Schema::hasColumn($names['roles'], $teamKey)) {
            Schema::table($names['roles'], function (Blueprint $table) use ($teamKey): void {
                $table->unsignedBigInteger($teamKey)->nullable()->after('id');
                $table->index($teamKey, 'roles_team_foreign_key_index');
            });

            $this->swapUnique($names['roles'], ['name', 'guard_name'], [$teamKey, 'name', 'guard_name']);
        }

        // model_has_roles: nullable team_id, drop the non-team PRIMARY KEY, add a
        // team-aware UNIQUE index so a user can hold a role in multiple teams.
        if (Schema::hasTable($names['model_has_roles']) && ! Schema::hasColumn($names['model_has_roles'], $teamKey)) {
            $this->addTeamToPivot(
                $names['model_has_roles'],
                $teamKey,
                'model_has_roles_team_foreign_key_index',
                [$teamKey, $roleKey, $morphKey, 'model_type'],
                'model_has_roles_role_model_type_primary'
            );
        }

        // model_has_permissions: same treatment.
        if (Schema::hasTable($names['model_has_permissions']) && ! Schema::hasColumn($names['model_has_permissions'], $teamKey)) {
            $this->addTeamToPivot(
                $names['model_has_permissions'],
                $teamKey,
                'model_has_permissions_team_foreign_key_index',
                [$teamKey, $permissionKey, $morphKey, 'model_type'],
                'model_has_permissions_permission_model_type_primary'
            );
        }

        app('cache')->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        // No-op: teams columns are load-bearing once enabled; reversing would
        // drop role/permission scoping. Roll back by restoring from backup.
    }

    /**
     * Add a nullable team_id to a pivot, drop its existing PRIMARY KEY, and add a
     * team-aware UNIQUE index (nullable team_id can't sit in a MySQL PRIMARY KEY).
     */
    private function addTeamToPivot(string $table, string $teamKey, string $indexName, array $unique, string $uniqueName): void
    {
        Schema::table($table, function (Blueprint $t) use ($teamKey, $indexName, $unique, $uniqueName): void {
            $t->dropPrimary();
            $t->unsignedBigInteger($teamKey)->nullable();
            $t->index($teamKey, $indexName);
            $t->unique($unique, $uniqueName);
        });
    }

    private function swapUnique(string $table, array $old, array $new): void
    {
        Schema::table($table, function (Blueprint $t) use ($old, $new): void {
            $t->dropUnique($old);
            $t->unique($new);
        });
    }
};
