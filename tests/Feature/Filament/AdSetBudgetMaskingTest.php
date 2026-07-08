<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\AdSetResource\Pages\EditAdSet;
use App\Filament\App\Resources\AdSetResource\Pages\ListAdSets;
use App\Models\AdSet;
use App\Models\AdvertisingAccount;
use App\Models\Campaign;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdSetBudgetMaskingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: AdSet}
     */
    private function setUpViewer(string $role): array
    {
        $this->seed(RolesSeeder::class);
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $user->currentTeam;
        setPermissionsTeamId($team->id);
        $user->assignRole($role);

        // AdSet is team-scoped only (not owner-scoped), so no user_id. Its related
        // records live in the same team so the edit form's required relationship
        // selects resolve under tenancy.
        $account = AdvertisingAccount::factory()->create(['team_id' => $team->id]);
        $campaign = Campaign::factory()->create(['team_id' => $team->id]);
        $adSet = AdSet::factory()->create([
            'team_id' => $team->id,
            'advertising_account_id' => $account->id,
            'campaign_id' => $campaign->id,
            'budget' => 50000,
        ]);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return [$user, $adSet];
    }

    public function test_free_user_serialization_masks_budget_without_mutation(): void
    {
        [, $adSet] = $this->setUpViewer('free');
        $fresh = $adSet->fresh();

        $this->assertSame('[hidden]', $fresh->toArray()['budget']);
        // Direct access is unmasked (no corruption).
        $this->assertSame('50000.00', (string) $fresh->budget);
    }

    public function test_manager_serialization_shows_real_budget(): void
    {
        [, $adSet] = $this->setUpViewer('manager');

        $this->assertSame('50000.00', (string) $adSet->fresh()->toArray()['budget']);
    }

    public function test_free_user_sees_masked_budget_in_the_table(): void
    {
        $this->setUpViewer('free');

        Livewire::test(ListAdSets::class)
            ->assertSee('[hidden]')
            ->assertDontSee('50,000');
    }

    public function test_free_user_budget_field_is_hidden_on_edit(): void
    {
        [, $adSet] = $this->setUpViewer('free');

        Livewire::test(EditAdSet::class, ['record' => $adSet->getKey()])
            ->assertFormFieldIsHidden('budget')
            ->assertSee('[hidden]');
    }

    public function test_manager_sees_the_real_budget_on_edit(): void
    {
        [, $adSet] = $this->setUpViewer('manager');

        Livewire::test(EditAdSet::class, ['record' => $adSet->getKey()])
            ->assertFormSet(['budget' => '50000.00']);
    }
}
