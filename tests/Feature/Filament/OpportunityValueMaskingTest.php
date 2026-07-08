<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\OpportunityResource\Pages\EditOpportunity;
use App\Filament\App\Resources\OpportunityResource\Pages\ListOpportunities;
use App\Models\Opportunity;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OpportunityValueMaskingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Opportunity}
     */
    private function setUpViewer(string $role): array
    {
        $this->seed(RolesSeeder::class);
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $user->currentTeam;
        setPermissionsTeamId($team->id);
        $user->assignRole($role);

        // Team-scoped: the opportunity must belong to the viewer's team to be visible.
        $opportunity = Opportunity::factory()->create([
            'team_id' => $team->id,
            'deal_size' => 50000,
        ]);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return [$user, $opportunity];
    }

    public function test_free_user_serialization_masks_deal_size_without_mutation(): void
    {
        [, $opportunity] = $this->setUpViewer('free');
        $fresh = $opportunity->fresh();

        $this->assertSame('[hidden]', $fresh->toArray()['deal_size']);
        // Direct access is unmasked (no corruption).
        $this->assertEquals(50000, $fresh->deal_size);
    }

    public function test_manager_serialization_shows_real_deal_size(): void
    {
        [, $opportunity] = $this->setUpViewer('manager');

        $this->assertEquals(50000, $opportunity->fresh()->toArray()['deal_size']);
    }

    public function test_free_user_sees_masked_deal_size_in_the_table(): void
    {
        $this->setUpViewer('free');

        Livewire::test(ListOpportunities::class)
            ->assertSee('[hidden]')
            ->assertDontSee('50,000');
    }

    public function test_manager_sees_real_deal_size_in_the_table(): void
    {
        $this->setUpViewer('manager');

        Livewire::test(ListOpportunities::class)
            ->assertSee('50,000.00')
            ->assertDontSee('[hidden]');
    }

    public function test_free_user_deal_size_field_is_hidden_on_edit(): void
    {
        [, $opportunity] = $this->setUpViewer('free');

        Livewire::test(EditOpportunity::class, ['record' => $opportunity->getKey()])
            ->assertFormFieldIsHidden('deal_size')
            ->assertSee('[hidden]');
    }

    public function test_manager_sees_the_deal_size_field_on_edit(): void
    {
        [, $opportunity] = $this->setUpViewer('manager');

        Livewire::test(EditOpportunity::class, ['record' => $opportunity->getKey()])
            ->assertFormFieldIsVisible('deal_size');
    }
}
