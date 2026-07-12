<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\MarketingCampaignResource\Pages\ViewMarketingCampaign;
use App\Models\MarketingCampaign;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewMarketingCampaignTest extends TestCase
{
    use RefreshDatabase;

    private function actAs(string $role): User
    {
        $this->seed(RolesSeeder::class);
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $user->currentTeam;
        setPermissionsTeamId($team->id);
        $user->assignRole($role);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return $user;
    }

    public function test_admin_can_mount_the_view_page_and_see_campaign_name(): void
    {
        $user = $this->actAs('admin');

        $campaign = MarketingCampaign::factory()->create([
            'team_id' => $user->currentTeam->id,
            'name' => 'Spring Renewal Blast',
        ]);

        Livewire::test(ViewMarketingCampaign::class, ['record' => $campaign->getKey()])
            ->assertOk()
            ->assertSee('Spring Renewal Blast');
    }
}
