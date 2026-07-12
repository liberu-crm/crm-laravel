<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\AdSetResource\Pages\ViewAdSet;
use App\Models\AdSet;
use App\Models\AdvertisingAccount;
use App\Models\Campaign;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewAdSetTest extends TestCase
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

    private function makeAdSet(User $user, float $budget = 50000): AdSet
    {
        $team = $user->currentTeam;

        // AdSet is team-scoped; its relations must live in the same team so the
        // infolist relationship entries resolve under tenancy.
        $account = AdvertisingAccount::factory()->create(['team_id' => $team->id]);
        $campaign = Campaign::factory()->create(['team_id' => $team->id]);

        return AdSet::factory()->create([
            'team_id' => $team->id,
            'advertising_account_id' => $account->id,
            'campaign_id' => $campaign->id,
            'budget' => $budget,
        ]);
    }

    public function test_admin_can_mount_the_view_page(): void
    {
        $user = $this->actAs('admin');
        $adSet = $this->makeAdSet($user);

        Livewire::test(ViewAdSet::class, ['record' => $adSet->getKey()])
            ->assertOk();
    }

    public function test_admin_sees_the_real_unmasked_budget(): void
    {
        $user = $this->actAs('admin');
        $adSet = $this->makeAdSet($user, 50000);

        Livewire::test(ViewAdSet::class, ['record' => $adSet->getKey()])
            ->assertOk()
            ->assertSee('$50,000.00')
            ->assertDontSee('[hidden]');
    }
}
