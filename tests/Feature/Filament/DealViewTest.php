<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\DealResource\Pages\ViewDeal;
use App\Models\Deal;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DealViewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Deal}
     */
    private function setUpViewer(string $role): array
    {
        $this->seed(RolesSeeder::class);
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $user->currentTeam;
        setPermissionsTeamId($team->id);
        $user->assignRole($role);

        // Owner-scoped (RestrictsToOwner): the deal must belong to the viewer so
        // a `free`/sales_rep can resolve it through the owner global scope.
        $deal = Deal::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'value' => 50000,
        ]);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return [$user, $deal];
    }

    public function test_view_page_mounts_for_admin(): void
    {
        [, $deal] = $this->setUpViewer('admin');

        Livewire::test(ViewDeal::class, ['record' => $deal->getKey()])
            ->assertOk()
            ->assertSee($deal->name);
    }

    public function test_free_user_sees_masked_value(): void
    {
        [, $deal] = $this->setUpViewer('free');

        Livewire::test(ViewDeal::class, ['record' => $deal->getKey()])
            ->assertOk()
            ->assertSee('[hidden]')
            ->assertDontSee('50,000');
    }

    public function test_manager_sees_real_value(): void
    {
        [, $deal] = $this->setUpViewer('manager');

        Livewire::test(ViewDeal::class, ['record' => $deal->getKey()])
            ->assertOk()
            ->assertSee('50,000');
    }
}
