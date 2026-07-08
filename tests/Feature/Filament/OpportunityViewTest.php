<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\OpportunityResource\Pages\ViewOpportunity;
use App\Models\Opportunity;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OpportunityViewTest extends TestCase
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

        $opportunity = Opportunity::factory()->create([
            'team_id' => $team->id,
            'deal_size' => 50000,
        ]);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return [$user, $opportunity];
    }

    public function test_admin_can_mount_the_view_page(): void
    {
        [, $opportunity] = $this->setUpViewer('admin');

        Livewire::test(ViewOpportunity::class, ['record' => $opportunity->getKey()])
            ->assertOk();
    }

    public function test_free_role_sees_masked_deal_size(): void
    {
        [, $opportunity] = $this->setUpViewer('free');

        Livewire::test(ViewOpportunity::class, ['record' => $opportunity->getKey()])
            ->assertOk()
            ->assertSee('[hidden]')
            ->assertDontSee('50,000');
    }

    public function test_manager_sees_real_deal_size(): void
    {
        [, $opportunity] = $this->setUpViewer('manager');

        Livewire::test(ViewOpportunity::class, ['record' => $opportunity->getKey()])
            ->assertOk()
            ->assertSee('50,000');
    }
}
