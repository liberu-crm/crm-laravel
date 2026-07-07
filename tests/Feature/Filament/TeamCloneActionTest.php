<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\TeamResource;
use App\Filament\Resources\TeamResource\Pages\ListTeams;
use App\Models\Pipeline;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeamCloneActionTest extends TestCase
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

    public function test_super_admin_can_clone_a_team_via_action(): void
    {
        $admin = $this->actingSuperAdmin();
        $source = Team::factory()->create(['user_id' => $admin->id]);
        Pipeline::factory()->create(['team_id' => $source->id]);

        Livewire::test(ListTeams::class)
            ->callTableAction('clone', $source, data: [
                'name' => 'Cloned via UI',
                'owner_id' => $admin->id,
            ]);

        $new = Team::where('name', 'Cloned via UI')->first();
        $this->assertNotNull($new);
        $this->assertSame(1, Pipeline::withoutGlobalScope('tenant')->where('team_id', $new->id)->count());
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
