<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\TeamResource;
use App\Filament\Resources\TeamResource\Pages\ListTeams;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeamResourceArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function actingSuperAdmin(): User
    {
        $this->seed(RolesSeeder::class);
        $admin = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        setPermissionsTeamId(null);
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $admin;
    }

    public function test_index_page_mounts_for_super_admin(): void
    {
        $this->actingSuperAdmin();

        $this->get('/admin/'.TeamResource::getSlug())->assertStatus(200);
    }

    public function test_super_admin_can_archive_team_via_action(): void
    {
        $this->actingSuperAdmin();
        $team = Team::factory()->create();

        Livewire::test(ListTeams::class)
            ->callTableAction('archive', $team);

        $this->assertNotNull($team->fresh()->archived_at);
    }

    public function test_super_admin_can_restore_team_via_action(): void
    {
        $this->actingSuperAdmin();
        $team = Team::factory()->create();
        $team->archive();

        Livewire::test(ListTeams::class)
            ->callTableAction('restore', $team);

        $this->assertNull($team->fresh()->archived_at);
    }

    public function test_archive_action_hidden_for_personal_team(): void
    {
        $this->actingSuperAdmin();
        $personal = Team::factory()->create(['personal_team' => true]);

        Livewire::test(ListTeams::class)
            ->assertTableActionHidden('archive', $personal);
    }

    public function test_resource_denies_non_super_admin(): void
    {
        $this->seed(RolesSeeder::class);
        $manager = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        setPermissionsTeamId(null);
        $manager->assignRole('manager');
        $this->actingAs($manager);

        $this->assertFalse(TeamResource::canAccess());
    }
}
