<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\AdResource\Pages\ViewAd;
use App\Models\Ad;
use App\Models\AdSet;
use App\Models\AdvertisingAccount;
use App\Models\Campaign;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewAdTest extends TestCase
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

    private function makeAd(User $user, string $name = 'Summer Sale Ad'): Ad
    {
        $team = $user->currentTeam;

        // Ad is team-scoped; all its NOT-NULL parents must live in the same team
        // so the record resolves under tenancy and the infolist relations render.
        $account = AdvertisingAccount::factory()->create(['team_id' => $team->id]);
        $campaign = Campaign::factory()->create(['team_id' => $team->id]);
        $adSet = AdSet::factory()->create([
            'team_id' => $team->id,
            'advertising_account_id' => $account->id,
            'campaign_id' => $campaign->id,
        ]);

        return Ad::factory()->create([
            'team_id' => $team->id,
            'advertising_account_id' => $account->id,
            'campaign_id' => $campaign->id,
            'ad_set_id' => $adSet->id,
            'name' => $name,
        ]);
    }

    public function test_admin_can_mount_the_view_page_and_see_the_ad_name(): void
    {
        $user = $this->actAs('admin');
        $ad = $this->makeAd($user, 'Summer Sale Ad');

        Livewire::test(ViewAd::class, ['record' => $ad->getKey()])
            ->assertOk()
            ->assertSee('Summer Sale Ad');
    }
}
