<?php

declare(strict_types=1);

namespace Tests\Feature\Teams;

use App\Models\Team;
use App\Models\User;
use App\Support\TenantContext;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_archive_sets_timestamp_and_marks_archived(): void
    {
        $team = Team::factory()->create();

        $team->archive();

        $this->assertNotNull($team->archived_at);
        $this->assertTrue($team->isArchived());
    }

    public function test_archive_refuses_personal_team(): void
    {
        $team = Team::factory()->create(['personal_team' => true]);

        $this->expectException(DomainException::class);

        $team->archive();
    }

    public function test_archive_is_idempotent(): void
    {
        $team = Team::factory()->create();
        $team->archive();
        $first = $team->archived_at;

        $team->archive();

        $this->assertEquals($first, $team->fresh()->archived_at);
    }

    public function test_archive_reassigns_stranded_members_to_personal_team(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $personal = $user->personalTeam();

        $shared = Team::factory()->create();
        $user->forceFill(['current_team_id' => $shared->id])->save();

        $shared->archive();

        $this->assertSame($personal->id, $user->fresh()->current_team_id);
    }

    public function test_restore_clears_archived_state(): void
    {
        $team = Team::factory()->create();
        $team->archive();

        $team->restore();

        $this->assertNull($team->fresh()->archived_at);
        $this->assertFalse($team->fresh()->isArchived());
    }

    public function test_global_scope_hides_archived_but_with_archived_reveals(): void
    {
        $active = Team::factory()->create();
        $archived = Team::factory()->create();
        $archived->archive();

        $ids = Team::query()->pluck('id');
        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($archived->id));

        $allIds = Team::query()->withArchived()->pluck('id');
        $this->assertTrue($allIds->contains($archived->id));
    }

    public function test_archived_team_drops_from_user_tenants_and_access(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $shared = Team::factory()->create(['user_id' => $user->id]);
        $user->teams()->attach($shared);
        $user->unsetRelation('ownedTeams')->unsetRelation('teams');

        $panel = filament()->getPanel('app');
        $this->assertTrue($user->getTenants($panel)->contains(fn (Team $t): bool => $t->id === $shared->id));
        $this->assertTrue($user->canAccessTenant($shared));

        $shared->archive();
        $user->unsetRelation('ownedTeams')->unsetRelation('teams');

        $this->assertFalse($user->getTenants($panel)->contains(fn (Team $t): bool => $t->id === $shared->id));
        $this->assertFalse($user->canAccessTenant($shared->fresh()));
    }

    public function test_default_tenant_is_never_an_archived_team(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $shared = Team::factory()->create(['user_id' => $user->id]);
        $user->forceFill(['current_team_id' => $shared->id])->save();
        $shared->archive();

        $panel = filament()->getPanel('app');
        $default = $user->fresh()->getDefaultTenant($panel);

        $this->assertTrue($default === null || ! $default->isArchived());
    }

    public function test_set_tenant_context_null_for_user_stranded_on_archived_team(): void
    {
        // current_team_id points at an archived team -> currentTeam relation
        // resolves to null via the global scope -> no tenant, no data leak.
        $user = User::factory()->create();
        $shared = Team::factory()->create();
        $user->forceFill(['current_team_id' => $shared->id])->save();
        Team::query()->whereKey($shared->id)->update(['archived_at' => now()]);

        TenantContext::clear();
        $resolved = $user->fresh()->currentTeam;

        $this->assertNull($resolved);
    }
}
