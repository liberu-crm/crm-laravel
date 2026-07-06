<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * F4 backfill: pre-teams role assignments are stamped with the team they now
 * apply in — super_admin becomes global, every other assignment lands on the
 * user's current team (or stays global when the user has no current team).
 */
class RoleAssignmentBackfillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_backfill_stamps_each_assignment_with_the_right_team(): void
    {
        $teamA = Team::factory()->create();

        $userWithTeam = User::factory()->create(['current_team_id' => $teamA->id]);
        $userNoTeam = User::factory()->create(['current_team_id' => null]);
        $superUser = User::factory()->create(['current_team_id' => $teamA->id]);

        $managerId = DB::table('roles')->where('name', 'manager')->value('id');
        $superAdminId = DB::table('roles')->where('name', 'super_admin')->value('id');

        // Simulate legacy (pre-teams) rows: assignments written with no team.
        $rows = [
            // manager for a user with a current team -> should become teamA.
            ['role_id' => $managerId, 'model_type' => $userWithTeam->getMorphClass(), 'model_id' => $userWithTeam->id, 'team_id' => null],
            // manager for a user with no current team -> should stay null.
            ['role_id' => $managerId, 'model_type' => $userNoTeam->getMorphClass(), 'model_id' => $userNoTeam->id, 'team_id' => null],
            // super_admin, even stamped with a stray team -> should become null.
            ['role_id' => $superAdminId, 'model_type' => $superUser->getMorphClass(), 'model_id' => $superUser->id, 'team_id' => $teamA->id],
        ];
        DB::table('model_has_roles')->insert($rows);

        $migration = require base_path('database/migrations/2026_07_06_000003_backfill_role_assignment_teams.php');
        $migration->up();

        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $managerId,
            'model_id' => $userWithTeam->id,
            'team_id' => $teamA->id,
        ]);
        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $managerId,
            'model_id' => $userNoTeam->id,
            'team_id' => null,
        ]);
        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $superAdminId,
            'model_id' => $superUser->id,
            'team_id' => null,
        ]);
    }
}
