<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\CampaignResource\Pages\EditCampaign;
use App\Filament\App\Resources\CampaignResource\Pages\ListCampaigns;
use App\Models\Campaign;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CampaignBudgetMaskingTest extends TestCase
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

        // Campaign is team-scoped (IsTenantModel), not owner-scoped, so a free team
        // member sees every team campaign — no user_id needed.
        $campaign = Campaign::factory()->create([
            'team_id' => $team->id,
            'budget' => 50000,
        ]);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return [$user, $campaign];
    }

    public function test_free_user_serialization_masks_budget_without_mutation(): void
    {
        [, $campaign] = $this->setUpViewer('free');
        $fresh = $campaign->fresh();

        $this->assertSame('[hidden]', $fresh->toArray()['budget']);
        // Direct access is unmasked (no corruption).
        $this->assertSame('50000.00', (string) $fresh->budget);
    }

    public function test_manager_serialization_shows_real_budget(): void
    {
        [, $campaign] = $this->setUpViewer('manager');

        $this->assertSame('50000.00', (string) $campaign->fresh()->toArray()['budget']);
    }

    public function test_free_user_sees_masked_budget_in_the_table(): void
    {
        $this->setUpViewer('free');

        Livewire::test(ListCampaigns::class)
            ->assertSee('[hidden]')
            ->assertDontSee('50,000');
    }

    public function test_free_user_budget_field_is_hidden_on_edit(): void
    {
        [, $campaign] = $this->setUpViewer('free');

        Livewire::test(EditCampaign::class, ['record' => $campaign->getKey()])
            ->assertFormFieldIsHidden('budget')
            ->assertSee('[hidden]');
    }

    public function test_manager_sees_the_real_budget_on_edit(): void
    {
        [, $campaign] = $this->setUpViewer('manager');

        Livewire::test(EditCampaign::class, ['record' => $campaign->getKey()])
            ->assertFormSet(['budget' => '50000.00']);
    }
}
