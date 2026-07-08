<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\LeadResource\Pages\ViewLead;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadViewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Lead}
     */
    private function setUpViewer(string $role): array
    {
        $this->seed(RolesSeeder::class);
        $viewer = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $viewer->currentTeam;
        setPermissionsTeamId($team->id);
        $viewer->assignRole($role);

        // Owner-scoped: the viewer must own the lead to see it as a rep/free.
        $lead = Lead::factory()->create([
            'team_id' => $team->id,
            'user_id' => $viewer->id,
            'potential_value' => 50000,
        ]);

        $this->actingAs($viewer);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return [$viewer, $lead];
    }

    public function test_admin_can_mount_the_view_page(): void
    {
        [, $lead] = $this->setUpViewer('admin');

        Livewire::test(ViewLead::class, ['record' => $lead->getKey()])
            ->assertOk();
    }

    public function test_free_owner_sees_masked_potential_value(): void
    {
        [, $lead] = $this->setUpViewer('free');

        Livewire::test(ViewLead::class, ['record' => $lead->getKey()])
            ->assertOk()
            ->assertSee('[hidden]')
            ->assertDontSee('50,000');
    }

    public function test_manager_sees_the_real_potential_value(): void
    {
        [, $lead] = $this->setUpViewer('manager');

        Livewire::test(ViewLead::class, ['record' => $lead->getKey()])
            ->assertOk()
            ->assertSee('50,000');
    }
}
