<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\TeamBackupResource;
use App\Filament\Resources\TeamBackupResource\Pages\ListTeamBackups;
use App\Jobs\GenerateTeamBackup;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class TeamBackupResourceTest extends TestCase
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

        $this->get('/admin/'.TeamBackupResource::getSlug())->assertStatus(200);
    }

    public function test_generate_action_queues_a_backup(): void
    {
        Bus::fake();
        $this->actingSuperAdmin();
        $team = Team::factory()->create();

        Livewire::test(ListTeamBackups::class)
            ->callAction('generate', ['team_id' => $team->id]);

        $this->assertDatabaseHas('team_backups', [
            'team_id' => $team->id,
            'status' => 'pending',
        ]);
        Bus::assertDispatched(GenerateTeamBackup::class);
    }

    public function test_resource_denies_non_super_admin(): void
    {
        $this->seed(RolesSeeder::class);
        $manager = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        setPermissionsTeamId(null);
        $manager->assignRole('manager');
        $this->actingAs($manager);

        $this->assertFalse(TeamBackupResource::canAccess());
    }
}
