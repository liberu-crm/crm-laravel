<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\CampaignResource;
use App\Filament\App\Resources\CampaignResource\Pages\ViewCampaign;
use App\Models\Campaign;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CampaignViewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Campaign}
     */
    private function setUpViewer(string $role): array
    {
        $this->seed(RolesSeeder::class);
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $user->currentTeam;
        setPermissionsTeamId($team->id);
        $user->assignRole($role);

        $campaign = Campaign::factory()->create([
            'team_id' => $team->id,
            'budget' => 50000,
        ]);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return [$user, $campaign];
    }

    public function test_admin_can_mount_the_view_page(): void
    {
        [, $campaign] = $this->setUpViewer('admin');

        Livewire::test(ViewCampaign::class, ['record' => $campaign->getKey()])
            ->assertOk();
    }

    public function test_free_role_cannot_view_campaigns(): void
    {
        // free has no advertising access under enforcement, so it can't reach a
        // Campaign detail view at all (stronger than budget masking).
        $this->setUpViewer('free');

        $this->assertFalse(CampaignResource::canViewAny());
    }

    public function test_manager_sees_real_budget(): void
    {
        [, $campaign] = $this->setUpViewer('manager');

        Livewire::test(ViewCampaign::class, ['record' => $campaign->getKey()])
            ->assertOk()
            ->assertSee('50,000');
    }
}
